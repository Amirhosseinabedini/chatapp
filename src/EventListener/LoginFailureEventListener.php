<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

#[AsEventListener(event: LoginFailureEvent::class)]
class LoginFailureEventListener
{
    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack
    ) {
    }

    public function __invoke(LoginFailureEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        // Get client IP address
        $clientIp = $request ? $request->getClientIp() : 'unknown';
        
        // Get user agent
        $userAgent = $request ? $request->headers->get('User-Agent') : 'unknown';
        
        // Get attempted email from request
        $attemptedEmail = $request ? $request->getPayload()->getString('email') : 'unknown';
        
        // Get exception details
        $exception = $event->getException();
        
        // Log failed login attempt
        $this->logger->warning('User login failed', [
            'attempted_email' => $attemptedEmail,
            'client_ip' => $clientIp,
            'user_agent' => $userAgent,
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'timestamp' => new \DateTime(),
            'session_id' => $request ? $request->getSession()->getId() : null,
        ]);
    }
}
