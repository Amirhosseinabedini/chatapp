<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[Route('/chat', name: 'app_chat_')]
#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        private NotificationRepository $notificationRepository,
        private HubInterface $hub,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        // Force fresh user retrieval to avoid caching issues
        $currentUser = $this->getUser();
        if ($currentUser) {
            // Refresh user from database to ensure we have latest data
            $currentUser = $this->userRepository->find($currentUser->getId());
        }
        
        $conversations = $this->messageRepository->getConversationPartners($currentUser);
        
        // Get user details for each conversation partner
        $conversationData = [];
        foreach ($conversations as $conversation) {
            $partner = $this->userRepository->find($conversation['partner_id']);
            if ($partner) {
                $conversationData[] = [
                    'user' => $partner,
                    'last_message_at' => $conversation['last_message_at'],
                    'unread_count' => $conversation['unread_count'],
                ];
            }
        }

        $response = $this->render('chat/index.html.twig', [
            'conversations' => $conversationData,
            'current_user' => $currentUser,
        ]);
        
        // Add aggressive cache-busting headers
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        $response->headers->set('ETag', md5(serialize([$currentUser->getId(), time()])));
        
        return $response;
    }

    #[Route('/conversation/{userId}', name: 'conversation', requirements: ['userId' => '\d+'])]
    public function conversation(int $userId): Response
    {
        $currentUser = $this->getUser();
        $otherUser = $this->userRepository->find($userId);
        
        if (!$otherUser) {
            throw $this->createNotFoundException('User not found');
        }

        if ($otherUser->getId() === $currentUser->getId()) {
            throw $this->createNotFoundException('Cannot chat with yourself');
        }

        // Mark messages as read
        $this->messageRepository->markMessagesAsRead($currentUser, $otherUser);

        // Get recent messages
        $messages = $this->messageRepository->getMessagesBetweenUsers($currentUser, $otherUser, 50);

        $response = $this->render('chat/conversation.html.twig', [
            'other_user' => $otherUser,
            'messages' => array_reverse($messages), // Show oldest first
            'current_user' => $currentUser,
        ]);
        
        // Add aggressive cache-busting headers
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        $response->headers->set('ETag', md5(serialize([$currentUser->getId(), $otherUser->getId(), time()])));
        
        return $response;
    }

    #[Route('/send', name: 'send_message', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['recipient_id']) || !isset($data['content'])) {
            return new JsonResponse(['error' => 'Missing required fields'], 400);
        }

        $recipient = $this->userRepository->find($data['recipient_id']);
        if (!$recipient) {
            return new JsonResponse(['error' => 'Recipient not found'], 404);
        }

        // Create message
        $message = new Message();
        $message->setSender($currentUser);
        $message->setRecipient($recipient);
        $message->setContent($data['content']);
        $message->setMessageType($data['message_type'] ?? 'text');

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Mark as delivered
        $message->markAsDelivered();
        $this->entityManager->flush();

        // Create notification for recipient (using Message table)
        $this->notificationRepository->createMessageNotification(
            $recipient,
            $currentUser,
            $data['content'],
            $message->getId()
        );

        // Publish to Mercure
        $this->publishMessage($message);

        // Send push notification if recipient is offline
        if (!$recipient->isOnline()) {
            $this->sendPushNotification($recipient, $currentUser, $data['content'], $message->getId());
        }

        // Log message sent
        $this->logger->info('Message sent', [
            'sender_id' => $currentUser->getId(),
            'recipient_id' => $recipient->getId(),
            'message_id' => $message->getId(),
            'content_length' => strlen($data['content']),
        ]);

        return new JsonResponse([
            'success' => true,
            'message_id' => $message->getId(),
            'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/messages/{userId}', name: 'get_messages', requirements: ['userId' => '\d+'])]
    public function getMessages(int $userId, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $otherUser = $this->userRepository->find($userId);
        
        if (!$otherUser) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $offset = (int) $request->query->get('offset', 0);
        $limit = min((int) $request->query->get('limit', 50), 100); // Max 100 messages

        $messages = $this->messageRepository->getMessagesBetweenUsers($currentUser, $otherUser, $limit, $offset);

        $messageData = [];
        foreach ($messages as $message) {
            $messageData[] = [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'sender_id' => $message->getSender()->getId(),
                'sender_name' => $message->getSender()->getDisplayNameOrEmail(),
                'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
                'read_at' => $message->getReadAt()?->format('Y-m-d H:i:s'),
                'delivered_at' => $message->getDeliveredAt()?->format('Y-m-d H:i:s'),
                'message_type' => $message->getMessageType(),
            ];
        }

        return new JsonResponse([
            'messages' => $messageData,
            'has_more' => count($messages) === $limit,
        ]);
    }

    #[Route('/search', name: 'search_messages')]
    public function searchMessages(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return new JsonResponse(['error' => 'Query too short'], 400);
        }

        $messages = $this->messageRepository->searchMessages($currentUser, $query, 20);

        $messageData = [];
        foreach ($messages as $message) {
            $messageData[] = [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'sender_id' => $message->getSender()->getId(),
                'sender_name' => $message->getSender()->getDisplayNameOrEmail(),
                'recipient_id' => $message->getRecipient()->getId(),
                'recipient_name' => $message->getRecipient()->getDisplayNameOrEmail(),
                'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
                'message_type' => $message->getMessageType(),
            ];
        }

        return new JsonResponse(['messages' => $messageData]);
    }

    #[Route('/users', name: 'get_users')]
    public function getUsers(): JsonResponse
    {
        // Force fresh user retrieval to avoid caching issues
        $currentUser = $this->getUser();
        if ($currentUser) {
            // Refresh user from database to ensure we have latest data
            $currentUser = $this->userRepository->find($currentUser->getId());
        }
        
        $users = $this->userRepository->findAll();

        $userData = [];
        foreach ($users as $user) {
            if ($user->getId() !== $currentUser->getId()) {
                $userData[] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'display_name' => $user->getDisplayName(),
                    'name' => $user->getDisplayNameOrEmail(),
                    'is_online' => $user->isOnline(),
                    'last_seen_at' => $user->getLastSeenAt()?->format('Y-m-d H:i:s'),
                    'avatar' => $user->getAvatar(),
                ];
            }
        }

        $response = new JsonResponse(['users' => $userData]);
        
        // Add cache-busting headers
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        
        return $response;
    }

    #[Route('/search-users', name: 'search_users')]
    public function searchUsers(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return new JsonResponse(['users' => []]);
        }

        $users = $this->userRepository->searchUsers($query, $currentUser);

        $userData = [];
        foreach ($users as $user) {
            $userData[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'display_name' => $user->getDisplayName(),
                'name' => $user->getDisplayNameOrEmail(),
                'is_online' => $user->isOnline(),
                'last_seen_at' => $user->getLastSeenAt()?->format('Y-m-d H:i:s'),
                'avatar' => $user->getAvatar(),
            ];
        }

        return new JsonResponse(['users' => $userData]);
    }

    #[Route('/start-chat', name: 'start_chat', methods: ['POST'])]
    public function startChat(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['user_id'])) {
            return new JsonResponse(['error' => 'User ID is required'], 400);
        }

        $otherUser = $this->userRepository->find($data['user_id']);
        if (!$otherUser) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        if ($otherUser->getId() === $currentUser->getId()) {
            return new JsonResponse(['error' => 'Cannot start chat with yourself'], 400);
        }

        // Check if conversation already exists
        $existingConversation = $this->messageRepository->getMessagesBetweenUsers($currentUser, $otherUser, 1);
        
        return new JsonResponse([
            'success' => true,
            'conversation_url' => '/chat/conversation/' . $otherUser->getId(),
            'user' => [
                'id' => $otherUser->getId(),
                'email' => $otherUser->getEmail(),
                'display_name' => $otherUser->getDisplayName(),
                'name' => $otherUser->getDisplayNameOrEmail(),
                'is_online' => $otherUser->isOnline(),
                'last_seen_at' => $otherUser->getLastSeenAt()?->format('Y-m-d H:i:s'),
                'avatar' => $otherUser->getAvatar(),
            ],
            'is_new_conversation' => empty($existingConversation)
        ]);
    }

    private function publishMessage(Message $message): void
    {
        $topic = sprintf('https://chatapp.local/conversation/%d-%d', 
            min($message->getSender()->getId(), $message->getRecipient()->getId()),
            max($message->getSender()->getId(), $message->getRecipient()->getId())
        );

        $data = json_encode([
            'type' => 'message',
            'message' => [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'sender_id' => $message->getSender()->getId(),
                'sender_name' => $message->getSender()->getDisplayNameOrEmail(),
                'recipient_id' => $message->getRecipient()->getId(),
                'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
                'message_type' => $message->getMessageType(),
            ],
        ], JSON_THROW_ON_ERROR);

        $this->hub->publish(new Update($topic, $data, false));
    }

    private function sendPushNotification(User $recipient, User $sender, string $content, int $messageId): void
    {
        // Publish push notification via Mercure
        $topic = 'https://chatapp.local/notifications/' . $recipient->getId();
        
        $notificationData = [
            'title' => 'New Message',
            'body' => sprintf('%s: %s', $sender->getDisplayNameOrEmail(), 
                strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content),
            'icon' => '/images/icon.svg',
            'badge' => '/images/icon.svg',
            'tag' => 'chat-notification',
            'data' => [
                'url' => '/chat/conversation/' . $sender->getId(),
                'sender_id' => $sender->getId(),
                'sender_name' => $sender->getDisplayNameOrEmail(),
                'message_id' => $messageId,
                'type' => 'message'
            ],
            'actions' => [
                [
                    'action' => 'open',
                    'title' => 'Open Chat',
                    'icon' => '/images/icon.svg'
                ],
                [
                    'action' => 'dismiss',
                    'title' => 'Dismiss'
                ]
            ]
        ];

        $this->hub->publish(new Update($topic, json_encode($notificationData, JSON_THROW_ON_ERROR), false));
    }
}
