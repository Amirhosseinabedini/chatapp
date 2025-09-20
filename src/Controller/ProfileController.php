<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $user = $this->getUser();
        
        return $this->render('profile/index.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $user = $this->getUser();
        
        return $this->render('profile/edit.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/profile/settings', name: 'app_profile_settings')]
    public function settings(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $user = $this->getUser();
        
        return $this->render('profile/settings.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/profile/remove-avatar', name: 'app_profile_remove_avatar', methods: ['POST'])]
    public function removeAvatar(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Validate CSRF token
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('remove_avatar', $token)) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_profile_index');
        }
        
        $user = $this->getUser();
        
        // Remove the avatar from the user
        $user->setAvatar(null);
        $entityManager->flush();
        
        $this->addFlash('success', 'Avatar removed successfully.');
        
        return $this->redirectToRoute('app_profile_index');
    }
}