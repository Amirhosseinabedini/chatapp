<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getUnreadCount(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllAsRead(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 'true')
            ->set('n.readAt', ':now')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    public function markAsRead(int $notificationId, User $user): bool
    {
        $notification = $this->createQueryBuilder('n')
            ->where('n.id = :id')
            ->andWhere('n.user = :user')
            ->setParameter('id', $notificationId)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();

        if ($notification) {
            $notification->markAsRead();
            $this->getEntityManager()->flush();
            return true;
        }

        return false;
    }

    public function createMessageNotification(User $user, User $sender, string $content, ?int $messageId = null): Notification
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType('message');
        $notification->setTitle('New Message');
        $notification->setMessage(sprintf('You have a new message from %s', $sender->getDisplayNameOrEmail()));
        $notification->setData([
            'sender_id' => $sender->getId(),
            'sender_name' => $sender->getDisplayNameOrEmail(),
            'content' => $content,
            'message_id' => $messageId,
            'url' => sprintf('/chat/conversation/%d', $sender->getId())
        ]);

        $this->getEntityManager()->persist($notification);
        $this->getEntityManager()->flush();

        return $notification;
    }

    public function createGroupMessageNotification(User $user, string $groupName, User $sender, string $content, ?int $messageId = null): Notification
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType('group');
        $notification->setTitle('New Group Message');
        $notification->setMessage(sprintf('%s sent a message in %s', $sender->getDisplayNameOrEmail(), $groupName));
        $notification->setData([
            'sender_id' => $sender->getId(),
            'sender_name' => $sender->getDisplayNameOrEmail(),
            'group_name' => $groupName,
            'content' => $content,
            'message_id' => $messageId,
            'url' => '/groups'
        ]);

        $this->getEntityManager()->persist($notification);
        $this->getEntityManager()->flush();

        return $notification;
    }
}
