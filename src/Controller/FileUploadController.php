<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[Route('/upload', name: 'app_upload_')]
#[IsGranted('ROLE_USER')]
class FileUploadController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private HubInterface $hub,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/message-file', name: 'message_file', methods: ['POST'])]
    public function uploadMessageFile(Request $request, SluggerInterface $slugger): JsonResponse
    {
        $currentUser = $this->getUser();
        $file = $request->files->get('file');
        $recipientId = $request->request->get('recipient_id');

        if (!$file) {
            return new JsonResponse(['error' => 'No file uploaded'], 400);
        }

        if (!$recipientId) {
            return new JsonResponse(['error' => 'Recipient ID required'], 400);
        }

        $recipient = $this->userRepository->find($recipientId);
        if (!$recipient) {
            return new JsonResponse(['error' => 'Recipient not found'], 404);
        }

        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return new JsonResponse(['error' => $validation['message']], 400);
        }

        try {
            // Generate unique filename
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

            // Determine upload directory based on file type
            $uploadDir = $this->getUploadDirectory($file->getMimeType());
            $uploadPath = $this->getParameter('kernel.project_dir') . '/public' . $uploadDir;

            // Create directory if it doesn't exist
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Move file
            $file->move($uploadPath, $newFilename);

            // Create message
            $message = new Message();
            $message->setSender($currentUser);
            $message->setRecipient($recipient);
            $message->setContent($file->getClientOriginalName());
            $message->setMessageType($this->getMessageType($file->getMimeType()));
            $message->setFilePath($uploadDir . '/' . $newFilename);
            $message->setFileName($file->getClientOriginalName());
            $message->setFileSize($file->getSize());
            $message->setFileType($file->getMimeType());

            $this->entityManager->persist($message);
            $this->entityManager->flush();

            // Mark as delivered
            $message->markAsDelivered();
            $this->entityManager->flush();

            // Publish to Mercure
            $this->publishFileMessage($message);

            // Log file upload
            $this->logger->info('File uploaded', [
                'sender_id' => $currentUser->getId(),
                'recipient_id' => $recipient->getId(),
                'message_id' => $message->getId(),
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType(),
            ]);

            return new JsonResponse([
                'success' => true,
                'message_id' => $message->getId(),
                'file_url' => $uploadDir . '/' . $newFilename,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType(),
                'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            ]);

        } catch (FileException $e) {
            $this->logger->error('File upload failed', [
                'error' => $e->getMessage(),
                'user_id' => $currentUser->getId(),
            ]);

            return new JsonResponse(['error' => 'Failed to upload file'], 500);
        }
    }

    #[Route('/preview/{filename}', name: 'preview_file', requirements: ['filename' => '.+'])]
    public function previewFile(string $filename): Response
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/files/' . $filename;
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

        $mimeType = mime_content_type($filePath);
        $content = file_get_contents($filePath);

        return new Response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline',
        ]);
    }

    #[Route('/download/{filename}', name: 'download_file', requirements: ['filename' => '.+'])]
    public function downloadFile(string $filename): Response
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/files/' . $filename;
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

        $mimeType = mime_content_type($filePath);
        $content = file_get_contents($filePath);

        return new Response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function validateFile($file): array
    {
        // Check file size (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file->getSize() > $maxSize) {
            return ['valid' => false, 'message' => 'File size must be less than 10MB'];
        }

        // Check file type
        $allowedTypes = [
            // Images
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            // Documents
            'application/pdf', 'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain', 'text/csv',
            // Archives
            'application/zip', 'application/x-rar-compressed',
        ];

        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return ['valid' => false, 'message' => 'File type not allowed'];
        }

        return ['valid' => true];
    }

    private function getUploadDirectory(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return '/uploads/images';
        } elseif (str_starts_with($mimeType, 'application/pdf') || 
                  str_starts_with($mimeType, 'application/msword') ||
                  str_starts_with($mimeType, 'application/vnd.openxmlformats') ||
                  str_starts_with($mimeType, 'text/')) {
            return '/uploads/documents';
        } else {
            return '/uploads/files';
        }
    }

    private function getMessageType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'application/pdf') || 
                  str_starts_with($mimeType, 'application/msword') ||
                  str_starts_with($mimeType, 'application/vnd.openxmlformats') ||
                  str_starts_with($mimeType, 'text/')) {
            return 'document';
        } else {
            return 'file';
        }
    }

    private function publishFileMessage(Message $message): void
    {
        $topic = sprintf('https://chatapp.local/conversation/%d-%d', 
            min($message->getSender()->getId(), $message->getRecipient()->getId()),
            max($message->getSender()->getId(), $message->getRecipient()->getId())
        );

        $data = json_encode([
            'type' => 'message',
            'message' => [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'sender_id' => $message->getSender()->getId(),
                'sender_name' => $message->getSender()->getDisplayNameOrEmail(),
                'recipient_id' => $message->getRecipient()->getId(),
                'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
                'message_type' => $message->getMessageType(),
                'file_path' => $message->getFilePath(),
                'file_name' => $message->getFileName(),
                'file_size' => $message->getFileSize(),
                'file_type' => $message->getFileType(),
            ],
        ], JSON_THROW_ON_ERROR);

        $this->hub->publish(new Update($topic, $data, false));
    }
}

