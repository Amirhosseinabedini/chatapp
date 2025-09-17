<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class)]
class LogoutEventListener
{
    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack
    ) {
    }

    public function __invoke(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        $request = $this->requestStack->getCurrentRequest();
        
        // Get client IP address
        $clientIp = $request ? $request->getClientIp() : 'unknown';
        
        // Get user agent
        $userAgent = $request ? $request->headers->get('User-Agent') : 'unknown';
        
        // Log user logout
        $this->logger->info('User logout', [
            'user_id' => $user ? $user->getUserIdentifier() : 'unknown',
            'user_email' => $user ? $user->getUserIdentifier() : 'unknown',
            'client_ip' => $clientIp,
            'user_agent' => $userAgent,
            'timestamp' => new \DateTime(),
            'session_id' => $request ? $request->getSession()->getId() : null,
        ]);
    }
}
