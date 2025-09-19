<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[Route('/push-notifications', name: 'app_push_')]
#[IsGranted('ROLE_USER')]
class PushNotificationController extends AbstractController
{
    public function __construct(
        private HubInterface $hub
    ) {
    }

    #[Route('/subscribe', name: 'subscribe', methods: ['POST'])]
    public function subscribe(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $endpoint = $data['endpoint'] ?? null;
        $keys = $data['keys'] ?? [];

        if (!$endpoint) {
            return new JsonResponse(['error' => 'Endpoint is required'], 400);
        }

        // Store subscription in database or session
        $user = $this->getUser();
        $subscription = [
            'endpoint' => $endpoint,
            'keys' => $keys,
            'user_id' => $user->getId(),
            'created_at' => new \DateTimeImmutable()
        ];

        // In a real implementation, you would store this in the database
        // For now, we'll just return success
        return new JsonResponse(['success' => true]);
    }

    #[Route('/send', name: 'send', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? 'New Message';
        $body = $data['body'] ?? 'You have a new message';
        $url = $data['url'] ?? '/';

        // Publish to Mercure for real-time delivery
        $topic = 'https://chatapp.local/notifications';
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => '/images/icon.svg',
            'badge' => '/images/icon.svg',
            'timestamp' => time()
        ], JSON_THROW_ON_ERROR);

        $this->hub->publish(new Update($topic, $payload, false));

        return new JsonResponse(['success' => true]);
    }
}

