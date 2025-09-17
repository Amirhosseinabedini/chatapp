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
        $key = $_ENV['MERCURE_SUBSCRIBER_JWT_KEY'] ?? 'ChangeThisMercureJWTSecret_ReplaceMe';
        $topic = 'https://chatapp.local/test';

        // Issue a JWT that allows subscribing to the test topic
        $payload = [
            'mercure' => ['subscribe' => [$topic]],
            'exp' => time() + 3600,
        ];

        $token = JWT::encode($payload, $key, 'HS256');

        // Set cookie for Mercure hub path so the browser sends it to the hub
        $cookie = Cookie::create('mercureAuthorization', 'Bearer '.$token)
            ->withPath('/.well-known/mercure')
            ->withHttpOnly(true)
            ->withSecure(false) // local dev over HTTP
            ->withSameSite('lax');

        $response = new Response('OK');
        $response->headers->setCookie($cookie);
        return $response;
    }
}
