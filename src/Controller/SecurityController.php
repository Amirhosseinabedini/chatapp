<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Redirect if user is already logged in
        if ($this->getUser()) {
            $this->logger->info('User already logged in, redirecting', [
                'user_id' => $this->getUser()->getUserIdentifier(),
                'timestamp' => new \DateTime(),
            ]);
            return $this->redirectToRoute('app_groups_index');
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // Log login page access
        $this->logger->info('Login page accessed', [
            'last_username' => $lastUsername,
            'has_error' => $error !== null,
            'error_message' => $error?->getMessage(),
            'timestamp' => new \DateTime(),
        ]);

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall.
        // The LogoutEventListener will handle the actual cleanup.
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
