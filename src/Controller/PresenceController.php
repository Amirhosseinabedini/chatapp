<?php

namespace App\Controller;

use App\Entity\User;
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

#[Route('/presence', name: 'app_presence_')]
#[IsGranted('ROLE_USER')]
class PresenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private HubInterface $hub,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/online', name: 'set_online', methods: ['POST'])]
    public function setOnline(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user->isOnline()) {
            $user->setIsOnline(true);
            $user->setLastSeenAt(new \DateTimeImmutable());
            $this->entityManager->flush();
            
            $this->publishPresenceUpdate($user, 'online');
            
            $this->logger->info('User came online', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
            ]);
        }

        return new JsonResponse(['status' => 'online']);
    }

    #[Route('/offline', name: 'set_offline', methods: ['POST'])]
    public function setOffline(): JsonResponse
    {
        $user = $this->getUser();
        
        if ($user->isOnline()) {
            $user->setIsOnline(false);
            $user->setLastSeenAt(new \DateTimeImmutable());
            $this->entityManager->flush();
            
            $this->publishPresenceUpdate($user, 'offline');
            
            $this->logger->info('User went offline', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
            ]);
        }

        return new JsonResponse(['status' => 'offline']);
    }

    #[Route('/typing/{userId}', name: 'typing', requirements: ['userId' => '\d+'], methods: ['POST'])]
    public function typing(int $userId, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $targetUser = $this->userRepository->find($userId);
        
        if (!$targetUser) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $isTyping = $data['typing'] ?? false;

        // Publish typing indicator
        $topic = sprintf('https://chatapp.local/conversation/%d-%d', 
            min($currentUser->getId(), $targetUser->getId()),
            max($currentUser->getId(), $targetUser->getId())
        );

        $typingData = json_encode([
            'type' => 'typing',
            'user_id' => $currentUser->getId(),
            'user_name' => $currentUser->getDisplayNameOrEmail(),
            'typing' => $isTyping,
        ], JSON_THROW_ON_ERROR);

        $this->hub->publish(new Update($topic, $typingData, false));

        return new JsonResponse(['success' => true]);
    }

    #[Route('/status', name: 'get_status')]
    public function getStatus(): JsonResponse
    {
        $currentUser = $this->getUser();
        
        return new JsonResponse([
            'user_id' => $currentUser->getId(),
            'is_online' => $currentUser->isOnline(),
            'last_seen_at' => $currentUser->getLastSeenAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/users', name: 'get_online_users')]
    public function getOnlineUsers(): JsonResponse
    {
        $currentUser = $this->getUser();
        $onlineUsers = $this->userRepository->createQueryBuilder('u')
            ->where('u.isOnline = true')
            ->andWhere('u.id != :currentUserId')
            ->setParameter('currentUserId', $currentUser->getId())
            ->getQuery()
            ->getResult();

        $userData = [];
        foreach ($onlineUsers as $user) {
            $userData[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'display_name' => $user->getDisplayName(),
                'name' => $user->getDisplayNameOrEmail(),
                'last_seen_at' => $user->getLastSeenAt()?->format('Y-m-d H:i:s'),
                'avatar' => $user->getAvatar(),
            ];
        }

        return new JsonResponse(['users' => $userData]);
    }

    private function publishPresenceUpdate(User $user, string $status): void
    {
        $topic = 'https://chatapp.local/presence';
        
        $data = json_encode([
            'type' => 'presence',
            'user_id' => $user->getId(),
            'user_name' => $user->getDisplayNameOrEmail(),
            'status' => $status,
            'last_seen_at' => $user->getLastSeenAt()?->format('Y-m-d H:i:s'),
        ], JSON_THROW_ON_ERROR);

        $this->hub->publish(new Update($topic, $data, false));
    }
}

