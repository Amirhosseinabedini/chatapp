<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class)]
class LogoutEventListener
{
    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        $request = $this->requestStack->getCurrentRequest();
        
        if ($user instanceof User) {
            // Update user's online status to offline
            $user->setIsOnline(false);
            $user->setLastSeenAt(new \DateTimeImmutable());
            $this->entityManager->flush();
        }
        
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

        // Clear all session data
        if ($request && $request->hasSession()) {
            $session = $request->getSession();
            
            // Clear all session attributes
            $session->clear();
            
            // Regenerate session ID to prevent session fixation attacks
            $session->migrate(true);
            
            // Force session destruction
            $session->invalidate();
            
            // Remove remember me cookie if it exists
            $response = $event->getResponse();
            if ($response) {
                $response->headers->clearCookie('REMEMBERME');
                $response->headers->clearCookie('PHPSESSID');
                // Clear any other potential session cookies
                $response->headers->clearCookie('symfony_session');
            }
        }
    }
}
