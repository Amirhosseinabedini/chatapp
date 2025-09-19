<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'api_notifications', methods: ['GET'])]
    public function getNotifications(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        
        // Mock notification data - in a real app, you'd fetch from database
        $notifications = [
            [
                'id' => 1,
                'type' => 'message',
                'title' => 'New Message',
                'message' => 'You have a new message from John Doe',
                'isRead' => false,
                'createdAt' => (new \DateTime())->format('c')
            ],
            [
                'id' => 2,
                'type' => 'group',
                'title' => 'Group Invitation',
                'message' => 'You have been invited to join "Project Team"',
                'isRead' => true,
                'createdAt' => (new \DateTime('-1 hour'))->format('c')
            ],
            [
                'id' => 3,
                'type' => 'system',
                'title' => 'System Update',
                'message' => 'The chat system has been updated with new features',
                'isRead' => false,
                'createdAt' => (new \DateTime('-2 hours'))->format('c')
            ]
        ];

        $unreadCount = count(array_filter($notifications, fn($n) => !$n['isRead']));

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

        // In a real app, you'd update the database here
        // For now, just return success

        return new JsonResponse([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    #[Route('/{id}/read', name: 'api_notification_mark_read', methods: ['POST'])]
    public function markAsRead(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // In a real app, you'd update the specific notification in the database
        // For now, just return success

        return new JsonResponse([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }
}
