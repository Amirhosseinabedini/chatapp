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

        $payload = [
            'mercure' => [
                // فقط اجازه subscribe به همین تاپیک تست
                'subscribe' => [$topic],
            ],
            'exp' => time() + 3600, // 1h
        ];

        $token = JWT::encode($payload, $key, 'HS256');

        // کوکی باید برای مسیر هاب ست شود
        $cookie = Cookie::create('mercureAuthorization', 'Bearer '.$token)
            ->withPath('/.well-known/mercure')
            ->withHttpOnly(true)
            ->withSecure(false) // روی لوکال HTTP هستیم
            ->withSameSite('lax');

        $resp = new Response('OK');
        $resp->headers->setCookie($cookie);
        return $resp;
    }
}
