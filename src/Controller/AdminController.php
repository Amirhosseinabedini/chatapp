<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\GroupRepository;
use App\Repository\MessageRepository;
use App\Repository\GroupMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'app_admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private GroupRepository $groupRepository,
        private MessageRepository $messageRepository,
        private GroupMessageRepository $groupMessageRepository,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/', name: 'dashboard')]
    public function dashboard(): Response
    {
        $stats = [
            'total_users' => $this->userRepository->count([]),
            'active_users' => $this->userRepository->count(['isOnline' => true]),
            'total_groups' => $this->groupRepository->count(['isActive' => true]),
            'total_messages' => $this->messageRepository->count([]) + $this->groupMessageRepository->count([]),
        ];

        $recentUsers = $this->userRepository->findBy([], ['createdAt' => 'DESC'], 10);
        $recentGroups = $this->groupRepository->findBy(['isActive' => true], ['createdAt' => 'DESC'], 10);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recent_users' => $recentUsers,
            'recent_groups' => $recentGroups,
        ]);
    }

    #[Route('/users', name: 'users')]
    public function users(): Response
    {
        $users = $this->userRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/{id}/ban', name: 'ban_user', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function banUser(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'No reason provided';

        // Ban user by removing ROLE_USER
        $user->setRoles(['ROLE_BANNED']);
        $this->entityManager->flush();

        $this->logger->warning('User banned by admin', [
            'banned_user_id' => $user->getId(),
            'banned_user_email' => $user->getEmail(),
            'admin_id' => $this->getUser()->getId(),
            'reason' => $reason,
        ]);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/users/{id}/unban', name: 'unban_user', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unbanUser(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Restore ROLE_USER
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->flush();

        $this->logger->info('User unbanned by admin', [
            'unbanned_user_id' => $user->getId(),
            'unbanned_user_email' => $user->getEmail(),
            'admin_id' => $this->getUser()->getId(),
        ]);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/groups', name: 'groups')]
    public function groups(): Response
    {
        $groups = $this->groupRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/groups.html.twig', [
            'groups' => $groups,
        ]);
    }

    #[Route('/groups/{id}/delete', name: 'delete_group', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteGroup(int $id): JsonResponse
    {
        $group = $this->groupRepository->find($id);
        if (!$group) {
            return new JsonResponse(['error' => 'Group not found'], 404);
        }

        $group->setIsActive(false);
        $this->entityManager->flush();

        $this->logger->warning('Group deleted by admin', [
            'group_id' => $group->getId(),
            'group_name' => $group->getName(),
            'admin_id' => $this->getUser()->getId(),
        ]);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/reports', name: 'reports')]
    public function reports(): Response
    {
        // In a real implementation, you would have a Report entity
        $reports = []; // Mock data

        return $this->render('admin/reports.html.twig', [
            'reports' => $reports,
        ]);
    }

    #[Route('/logs', name: 'logs')]
    public function logs(): Response
    {
        // Read recent log entries
        $logFile = $this->getParameter('kernel.logs_dir') . '/dev.log';
        $logs = [];
        
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logs = array_slice(array_reverse($lines), 0, 100); // Last 100 lines
        }

        return $this->render('admin/logs.html.twig', [
            'logs' => $logs,
        ]);
    }
}
