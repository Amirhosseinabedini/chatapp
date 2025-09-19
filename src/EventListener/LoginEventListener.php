<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginEventListener
{
    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $request = $this->requestStack->getCurrentRequest();
        
        // Update user's online status
        if ($user instanceof User) {
            $user->setIsOnline(true);
            $user->setLastSeenAt(new \DateTimeImmutable());
            $this->entityManager->flush();
        }
        
        // Get client IP address
        $clientIp = $request ? $request->getClientIp() : 'unknown';
        
        // Get user agent
        $userAgent = $request ? $request->headers->get('User-Agent') : 'unknown';
        
        // Log successful login
        $this->logger->info('User login successful', [
            'user_id' => $user->getUserIdentifier(),
            'user_email' => $user->getUserIdentifier(),
            'client_ip' => $clientIp,
            'user_agent' => $userAgent,
            'timestamp' => new \DateTime(),
            'session_id' => $request ? $request->getSession()->getId() : null,
        ]);
    }
}
