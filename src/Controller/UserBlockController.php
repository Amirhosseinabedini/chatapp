<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserBlockRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user-blocks', name: 'app_user_blocks_')]
#[IsGranted('ROLE_USER')]
class UserBlockController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserBlockRepository $userBlockRepository,
        private UserRepository $userRepository,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $currentUser = $this->getUser();
        $blockedUsers = $this->userBlockRepository->getBlockedUsers($currentUser);

        return $this->render('user_blocks/index.html.twig', [
            'blocked_users' => $blockedUsers,
            'current_user' => $currentUser,
        ]);
    }

    #[Route('/block', name: 'block', methods: ['POST'])]
    public function block(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['user_id'])) {
            return new JsonResponse(['error' => 'User ID is required'], 400);
        }

        $targetUser = $this->userRepository->find($data['user_id']);
        if (!$targetUser) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        if ($targetUser->getId() === $currentUser->getId()) {
            return new JsonResponse(['error' => 'You cannot block yourself'], 400);
        }

        // Check if already blocked
        if ($this->userBlockRepository->isUserBlocked($currentUser, $targetUser)) {
            return new JsonResponse(['error' => 'User is already blocked'], 400);
        }

        $reason = $data['reason'] ?? null;
        $block = $this->userBlockRepository->blockUser($currentUser, $targetUser, $reason);

        $this->logger->info('User blocked', [
            'blocker_id' => $currentUser->getId(),
            'blocked_id' => $targetUser->getId(),
            'reason' => $reason,
        ]);

        return new JsonResponse([
            'success' => true,
            'block_id' => $block->getId(),
            'blocked_user' => [
                'id' => $targetUser->getId(),
                'name' => $targetUser->getDisplayNameOrEmail(),
                'email' => $targetUser->getEmail(),
            ],
        ]);
    }

    #[Route('/unblock', name: 'unblock', methods: ['POST'])]
    public function unblock(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['user_id'])) {
            return new JsonResponse(['error' => 'User ID is required'], 400);
        }

        $targetUser = $this->userRepository->find($data['user_id']);
        if (!$targetUser) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $success = $this->userBlockRepository->unblockUser($currentUser, $targetUser);
        if (!$success) {
            return new JsonResponse(['error' => 'User is not blocked'], 400);
        }

        $this->logger->info('User unblocked', [
            'unblocker_id' => $currentUser->getId(),
            'unblocked_id' => $targetUser->getId(),
        ]);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/check/{userId}', name: 'check', requirements: ['userId' => '\d+'])]
    public function check(int $userId): JsonResponse
    {
        $currentUser = $this->getUser();
        $targetUser = $this->userRepository->find($userId);

        if (!$targetUser) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $isBlocked = $this->userBlockRepository->isUserBlocked($currentUser, $targetUser);
        $hasMutualBlocks = !empty($this->userBlockRepository->getMutualBlocks($currentUser, $targetUser));

        return new JsonResponse([
            'is_blocked' => $isBlocked,
            'has_mutual_blocks' => $hasMutualBlocks,
            'can_interact' => !$isBlocked && !$hasMutualBlocks,
        ]);
    }

    #[Route('/blocked-by', name: 'blocked_by')]
    public function blockedBy(): Response
    {
        $currentUser = $this->getUser();
        $blockers = $this->userBlockRepository->getBlockers($currentUser);

        return $this->render('user_blocks/blocked_by.html.twig', [
            'blockers' => $blockers,
            'current_user' => $currentUser,
        ]);
    }
}

