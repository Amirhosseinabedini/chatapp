<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\GroupMessage;
use App\Entity\User;
use App\Repository\GroupRepository;
use App\Repository\GroupMemberRepository;
use App\Repository\GroupMessageRepository;
use App\Repository\NotificationRepository;
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

#[Route('/group-messages', name: 'app_group_messages_')]
#[IsGranted('ROLE_USER')]
class GroupMessageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GroupRepository $groupRepository,
        private GroupMemberRepository $groupMemberRepository,
        private GroupMessageRepository $groupMessageRepository,
        private NotificationRepository $notificationRepository,
        private HubInterface $hub,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/send', name: 'send', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['group_id']) || !isset($data['content'])) {
            return new JsonResponse(['error' => 'Missing required fields'], 400);
        }

        $group = $this->groupRepository->find($data['group_id']);
        if (!$group) {
            return new JsonResponse(['error' => 'Group not found'], 404);
        }

        $member = $this->groupMemberRepository->findByGroupAndUser($group, $currentUser);
        if (!$member) {
            return new JsonResponse(['error' => 'You are not a member of this group'], 403);
        }

        // Create message
        $message = new GroupMessage();
        $message->setGroup($group);
        $message->setSender($currentUser);
        $message->setContent($data['content']);
        $message->setMessageType($data['message_type'] ?? 'text');

        // Handle reply
        if (isset($data['reply_to_id'])) {
            $replyTo = $this->groupMessageRepository->find($data['reply_to_id']);
            if ($replyTo && $replyTo->getGroup() === $group) {
                $message->setReplyTo($replyTo);
            }
        }

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Create notifications for all group members except sender
        $members = $this->groupMemberRepository->getGroupMembers($group);
        foreach ($members as $member) {
            if ($member->getUser()->getId() !== $currentUser->getId()) {
                $this->notificationRepository->createGroupMessageNotification(
                    $member->getUser(),
                    $group->getName(),
                    $currentUser,
                    $data['content'],
                    $message->getId()
                );
            }
        }

        // Publish to Mercure
        $this->publishGroupMessage($message);

        $this->logger->info('Group message sent', [
            'group_id' => $group->getId(),
            'message_id' => $message->getId(),
            'sender_id' => $currentUser->getId(),
            'content_length' => strlen($data['content']),
        ]);

        return new JsonResponse([
            'success' => true,
            'message_id' => $message->getId(),
            'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/{id}/messages', name: 'get_messages', requirements: ['id' => '\d+'])]
    public function getMessages(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $group = $this->groupRepository->find($id);

        if (!$group) {
            return new JsonResponse(['error' => 'Group not found'], 404);
        }

        $member = $this->groupMemberRepository->findByGroupAndUser($group, $currentUser);
        if (!$member) {
            return new JsonResponse(['error' => 'You are not a member of this group'], 403);
        }

        $offset = (int) $request->query->get('offset', 0);
        $limit = min((int) $request->query->get('limit', 50), 100);

        $messages = $this->groupMessageRepository->getGroupMessages($group, $limit, $offset);

        $messageData = [];
        foreach ($messages as $message) {
            $messageData[] = $this->formatMessageData($message);
        }

        return new JsonResponse([
            'messages' => $messageData,
            'has_more' => count($messages) === $limit,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $message = $this->groupMessageRepository->find($id);

        if (!$message) {
            return new JsonResponse(['error' => 'Message not found'], 404);
        }

        $member = $this->groupMemberRepository->findByGroupAndUser($message->getGroup(), $currentUser);
        if (!$member) {
            return new JsonResponse(['error' => 'You are not a member of this group'], 403);
        }

        // Only sender or moderators can edit
        if ($message->getSender() !== $currentUser && !$member->canDeleteMessages()) {
            return new JsonResponse(['error' => 'You cannot edit this message'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['content'])) {
            return new JsonResponse(['error' => 'Content is required'], 400);
        }

        $message->editMessage($data['content']);
        $this->entityManager->flush();

        // Publish update to Mercure
        $this->publishGroupMessageUpdate($message);

        $this->logger->info('Group message edited', [
            'message_id' => $message->getId(),
            'group_id' => $message->getGroup()->getId(),
            'editor_id' => $currentUser->getId(),
        ]);

        return new JsonResponse([
            'success' => true,
            'message' => $this->formatMessageData($message),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        $message = $this->groupMessageRepository->find($id);

        if (!$message) {
            return new JsonResponse(['error' => 'Message not found'], 404);
        }

        $member = $this->groupMemberRepository->findByGroupAndUser($message->getGroup(), $currentUser);
        if (!$member) {
            return new JsonResponse(['error' => 'You are not a member of this group'], 403);
        }

        // Only sender or moderators can delete
        if ($message->getSender() !== $currentUser && !$member->canDeleteMessages()) {
            return new JsonResponse(['error' => 'You cannot delete this message'], 403);
        }

        $message->markAsDeleted();
        $this->entityManager->flush();

        // Publish deletion to Mercure
        $this->publishGroupMessageDeletion($message);

        $this->logger->info('Group message deleted', [
            'message_id' => $message->getId(),
            'group_id' => $message->getGroup()->getId(),
            'deleter_id' => $currentUser->getId(),
        ]);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/pin', name: 'pin', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function pin(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        $message = $this->groupMessageRepository->find($id);

        if (!$message) {
            return new JsonResponse(['error' => 'Message not found'], 404);
        }

        $member = $this->groupMemberRepository->findByGroupAndUser($message->getGroup(), $currentUser);
        if (!$member || !$member->canPinMessages()) {
            return new JsonResponse(['error' => 'You cannot pin messages'], 403);
        }

        $message->setIsPinned(true);
        $this->entityManager->flush();

        $this->logger->info('Group message pinned', [
            'message_id' => $message->getId(),
            'group_id' => $message->getGroup()->getId(),
            'pinner_id' => $currentUser->getId(),
        ]);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/unpin', name: 'unpin', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unpin(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        $message = $this->groupMessageRepository->find($id);

        if (!$message) {
            return new JsonResponse(['error' => 'Message not found'], 404);
        }

        $member = $this->groupMemberRepository->findByGroupAndUser($message->getGroup(), $currentUser);
        if (!$member || !$member->canPinMessages()) {
            return new JsonResponse(['error' => 'You cannot unpin messages'], 403);
        }

        $message->setIsPinned(false);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/reaction', name: 'reaction', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reaction(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $message = $this->groupMessageRepository->find($id);

        if (!$message) {
            return new JsonResponse(['error' => 'Message not found'], 404);
        }

        $member = $this->groupMemberRepository->findByGroupAndUser($message->getGroup(), $currentUser);
        if (!$member) {
            return new JsonResponse(['error' => 'You are not a member of this group'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $emoji = $data['emoji'] ?? null;
        $action = $data['action'] ?? 'add'; // add or remove

        if (!$emoji) {
            return new JsonResponse(['error' => 'Emoji is required'], 400);
        }

        if ($action === 'add') {
            $message->addReaction($emoji, $currentUser);
        } else {
            $message->removeReaction($emoji, $currentUser);
        }

        $this->entityManager->flush();

        // Publish reaction update to Mercure
        $this->publishGroupMessageReaction($message, $emoji, $action, $currentUser);

        return new JsonResponse([
            'success' => true,
            'reactions' => $message->getReactions(),
        ]);
    }

    private function formatMessageData(GroupMessage $message): array
    {
        return [
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'sender_id' => $message->getSender()->getId(),
            'sender_name' => $message->getSender()->getDisplayNameOrEmail(),
            'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $message->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'message_type' => $message->getMessageType(),
            'is_edited' => $message->isEdited(),
            'is_pinned' => $message->isPinned(),
            'is_system_message' => $message->isSystemMessage(),
            'reactions' => $message->getReactions(),
            'reply_to' => $message->getReplyTo() ? [
                'id' => $message->getReplyTo()->getId(),
                'content' => $message->getReplyTo()->getContent(),
                'sender_name' => $message->getReplyTo()->getSender()->getDisplayNameOrEmail(),
            ] : null,
            'file_path' => $message->getFilePath(),
            'file_name' => $message->getFileName(),
            'file_size' => $message->getFileSize(),
            'file_type' => $message->getFileType(),
        ];
    }

    private function publishGroupMessage(GroupMessage $message): void
    {
        $topic = sprintf('https://chatapp.local/group/%d', $message->getGroup()->getId());

        $data = json_encode([
            'type' => 'group_message',
            'message' => $this->formatMessageData($message),
        ], JSON_THROW_ON_ERROR);

        $this->hub->publish(new Update($topic, $data, false));
    }

    private function publishGroupMessageUpdate(GroupMessage $message): void
    {
        $topic = sprintf('https://chatapp.local/group/%d', $message->getGroup()->getId());

        $data = json_encode([
            'type' => 'group_message_update',
            'message' => $this->formatMessageData($message),
        ], JSON_THROW_ON_ERROR);

        $this->hub->publish(new Update($topic, $data, false));
    }

    private function publishGroupMessageDeletion(GroupMessage $message): void
    {
        $topic = sprintf('https://chatapp.local/group/%d', $message->getGroup()->getId());

        $data = json_encode([
            'type' => 'group_message_delete',
            'message_id' => $message->getId(),
        ], JSON_THROW_ON_ERROR);

        $this->hub->publish(new Update($topic, $data, false));
    }

    private function publishGroupMessageReaction(GroupMessage $message, string $emoji, string $action, User $user): void
    {
        $topic = sprintf('https://chatapp.local/group/%d', $message->getGroup()->getId());

        $data = json_encode([
            'type' => 'group_message_reaction',
            'message_id' => $message->getId(),
            'emoji' => $emoji,
            'action' => $action,
            'user_id' => $user->getId(),
            'user_name' => $user->getDisplayNameOrEmail(),
            'reactions' => $message->getReactions(),
        ], JSON_THROW_ON_ERROR);

        $this->hub->publish(new Update($topic, $data, false));
    }
}

