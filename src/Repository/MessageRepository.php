<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Get messages between two users with pagination
     */
    public function getMessagesBetweenUsers(User $user1, User $user2, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.recipient = :user2) OR (m.sender = :user2 AND m.recipient = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get unread messages count for a user
     */
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

    /**
     * Get unread messages for a user
     */
    public function getUnreadMessages(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.recipient = :user')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead(User $recipient, User $sender): int
    {
        return $this->createQueryBuilder('m')
            ->update()
            ->set('m.readAt', ':now')
            ->where('m.recipient = :recipient')
            ->andWhere('m.sender = :sender')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('recipient', $recipient)
            ->setParameter('sender', $sender)
            ->getQuery()
            ->execute();
    }

    /**
     * Search messages by content
     */
    public function searchMessages(User $user, string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user OR m.recipient = :user)')
            ->andWhere('m.content LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get conversation partners for a user
     */
    public function getConversationPartners(User $user): array
    {
        // Get all messages where user is sender or recipient
        $messages = $this->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->leftJoin('m.recipient', 'r')
            ->where('m.sender = :user OR m.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $partners = [];
        
        foreach ($messages as $message) {
            $partner = $message->getSender()->getId() === $user->getId() 
                ? $message->getRecipient() 
                : $message->getSender();
            
            $partnerId = $partner->getId();
            
            if (!isset($partners[$partnerId])) {
                $partners[$partnerId] = [
                    'partner_id' => $partnerId,
                    'last_message_at' => $message->getCreatedAt(),
                    'unread_count' => 0,
                ];
            }
            
            // Count unread messages
            if ($message->getRecipient()->getId() === $user->getId() && !$message->getReadAt()) {
                $partners[$partnerId]['unread_count']++;
            }
        }
        
        // Sort by last message time
        uasort($partners, function($a, $b) {
            return $b['last_message_at'] <=> $a['last_message_at'];
        });
        
        return array_values($partners);
    }
}
