<?php
namespace App\Controller;

use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MercureAuthController extends AbstractController
{
    #[Route('/realtime/auth', name: 'app_realtime_auth')]
    public function auth(): Response
    {
        // Use the same key as MERCURE_SUBSCRIBER_JWT_KEY in Docker
        $key = $_ENV['MERCURE_SUBSCRIBER_JWT_KEY'] ?? 'ChangeThisMercureJWTSecret_ReplaceMe';
        $topic = 'https://chatapp.local/test';

        // Issue a JWT that allows subscribing to the test topic
        $payload = [
            'mercure' => ['subscribe' => [$topic]],
            'exp' => time() + 3600,
        ];

        $token = JWT::encode($payload, $key, 'HS256');

        // IMPORTANT: cookie value must be the raw JWT (no "Bearer " prefix)
        $cookie = Cookie::create('mercureAuthorization', $token)
            ->withPath('/.well-known/mercure')
            ->withHttpOnly(true)
            ->withSecure(false) // local dev over HTTP
            ->withSameSite('lax');

        $response = new Response('OK');
        $response->headers->setCookie($cookie);
        return $response;
    }
}
