<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findByUser(User $user, int $limit = 50): array
    {
        // Get messages where user is recipient (these are notifications)
        $messages = $this->createQueryBuilder('m')
            ->where('m.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Convert messages to notification format
        $notifications = [];
        foreach ($messages as $message) {
            $notifications[] = [
                'id' => $message->getId(),
                'type' => 'message',
                'title' => 'New Message',
                'message' => sprintf('You have a new message from %s', $message->getSender()->getDisplayNameOrEmail()),
                'isRead' => $message->getReadAt() !== null,
                'createdAt' => $message->getCreatedAt()->format('c'),
                'data' => [
                    'sender_id' => $message->getSender()->getId(),
                    'sender_name' => $message->getSender()->getDisplayNameOrEmail(),
                    'content' => $message->getContent(),
                    'message_id' => $message->getId(),
                    'url' => sprintf('/chat/conversation/%d', $message->getSender()->getId())
                ]
            ];
        }

        return $notifications;
    }

    public function getUnreadCount(User $user): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.recipient = :user')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllAsRead(User $user): int
    {
        $qb = $this->createQueryBuilder('m')
            ->update()
            ->set('m.readAt', ':now')
            ->where('m.recipient = :user')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable());

        return $qb->getQuery()->execute();
    }

    public function markAsRead(int $notificationId, User $user): bool
    {
        $message = $this->createQueryBuilder('m')
            ->where('m.id = :id')
            ->andWhere('m.recipient = :user')
            ->setParameter('id', $notificationId)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();

        if ($message) {
            $message->setReadAt(new \DateTimeImmutable());
            $this->getEntityManager()->flush();
            return true;
        }

        return false;
    }

    public function createMessageNotification(User $user, User $sender, string $content, ?int $messageId = null): array
    {
        // Since we're using the Message table, we don't need to create a separate notification
        // The message itself serves as the notification
        return [
            'id' => $messageId,
            'type' => 'message',
            'title' => 'New Message',
            'message' => sprintf('You have a new message from %s', $sender->getDisplayNameOrEmail()),
            'isRead' => false,
            'createdAt' => (new \DateTimeImmutable())->format('c'),
            'data' => [
                'sender_id' => $sender->getId(),
                'sender_name' => $sender->getDisplayNameOrEmail(),
                'content' => $content,
                'message_id' => $messageId,
                'url' => sprintf('/chat/conversation/%d', $sender->getId())
            ]
        ];
    }

    public function createGroupMessageNotification(User $user, string $groupName, User $sender, string $content, ?int $messageId = null): array
    {
        return [
            'id' => $messageId,
            'type' => 'group',
            'title' => 'New Group Message',
            'message' => sprintf('%s sent a message in %s', $sender->getDisplayNameOrEmail(), $groupName),
            'isRead' => false,
            'createdAt' => (new \DateTimeImmutable())->format('c'),
            'data' => [
                'sender_id' => $sender->getId(),
                'sender_name' => $sender->getDisplayNameOrEmail(),
                'group_name' => $groupName,
                'content' => $content,
                'message_id' => $messageId,
                'url' => '/groups'
            ]
        ];
    }
}