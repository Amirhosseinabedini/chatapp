<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class RealtimeController extends AbstractController
{
    #[Route('/realtime', name: 'app_realtime')]
    public function index(): Response
    {
        return $this->render('realtime/index.html.twig', [
            'topic' => 'https://chatapp.local/test',
        ]);
    }

    #[Route('/realtime/publish', name: 'app_realtime_publish')]
    public function publish(HubInterface $hub): Response
    {
        $data = json_encode([
            'type' => 'demo',
            'message' => 'Hello from Symfony + Mercure!',
            'ts' => time(),
        ], JSON_THROW_ON_ERROR);

        // public برای تست اولیه (بعداً private می‌کنیم)
        $hub->publish(new Update('https://chatapp.local/test', $data, false));
        return new Response('Published!');
    }
}
