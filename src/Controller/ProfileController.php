<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profile', name: 'app_profile_')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit', name: 'edit')]
    public function edit(Request $request, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        
        if ($request->isMethod('POST')) {
            $displayName = $request->request->get('display_name');
            $avatarFile = $request->files->get('avatar');

            // Update display name
            if ($displayName !== null) {
                $user->setDisplayName(trim($displayName) ?: null);
            }

            // Handle avatar upload
            if ($avatarFile) {
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $avatarFile->guessExtension();

                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($avatarFile->getMimeType(), $allowedTypes)) {
                    $this->addFlash('error', 'Only JPEG, PNG, GIF, and WebP images are allowed.');
                    return $this->render('profile/edit.html.twig', ['user' => $user]);
                }

                // Validate file size (max 2MB)
                if ($avatarFile->getSize() > 2 * 1024 * 1024) {
                    $this->addFlash('error', 'Avatar file size must be less than 2MB.');
                    return $this->render('profile/edit.html.twig', ['user' => $user]);
                }

                try {
                    $avatarFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                        $newFilename
                    );
                    
                    // Delete old avatar if exists
                    if ($user->getAvatar()) {
                        $oldAvatarPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . basename($user->getAvatar());
                        if (file_exists($oldAvatarPath)) {
                            unlink($oldAvatarPath);
                        }
                    }
                    
                    $user->setAvatar('/uploads/avatars/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload avatar.');
                    return $this->render('profile/edit.html.twig', ['user' => $user]);
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully!');
            
            $this->logger->info('User profile updated', [
                'user_id' => $user->getId(),
                'display_name_updated' => $displayName !== null,
                'avatar_updated' => $avatarFile !== null,
            ]);

            return $this->redirectToRoute('app_profile_index');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/remove-avatar', name: 'remove_avatar', methods: ['POST'])]
    public function removeAvatar(): Response
    {
        $user = $this->getUser();
        
        if ($user->getAvatar()) {
            $avatarPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . basename($user->getAvatar());
            if (file_exists($avatarPath)) {
                unlink($avatarPath);
            }
            
            $user->setAvatar(null);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Avatar removed successfully!');
            
            $this->logger->info('User avatar removed', [
                'user_id' => $user->getId(),
            ]);
        }

        return $this->redirectToRoute('app_profile_index');
    }
}

