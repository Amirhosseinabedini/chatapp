<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationRepository $notificationRepository
    ) {
    }

    #[Route('', name: 'api_notifications', methods: ['GET'])]
    public function getNotifications(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        
        // Get notifications from database (using Message table)
        $notifications = $this->notificationRepository->findByUser($user, 50);
        $unreadCount = $this->notificationRepository->getUnreadCount($user);

        return new JsonResponse([
            'success' => true,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount
        ]);
    }

    #[Route('/mark-all-read', name: 'api_notifications_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $updatedCount = $this->notificationRepository->markAllAsRead($user);

        return new JsonResponse([
            'success' => true,
            'message' => 'All notifications marked as read',
            'updatedCount' => $updatedCount
        ]);
    }

    #[Route('/{id}/read', name: 'api_notification_mark_read', methods: ['POST'])]
    public function markAsRead(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $success = $this->notificationRepository->markAsRead($id, $user);

        if ($success) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Notification not found or access denied'
        ], 404);
    }
}
