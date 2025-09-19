<?php

namespace App\Repository;

use App\Entity\Group;
use App\Entity\GroupMessage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupMessage>
 */
class GroupMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupMessage::class);
    }

    /**
     * Get messages for a group with pagination
     */
    public function getGroupMessages(Group $group, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.group = :group')
            ->andWhere('gm.deletedAt IS NULL')
            ->setParameter('group', $group)
            ->orderBy('gm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get pinned messages for a group
     */
    public function getPinnedMessages(Group $group): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.group = :group')
            ->andWhere('gm.isPinned = true')
            ->andWhere('gm.deletedAt IS NULL')
            ->setParameter('group', $group)
            ->orderBy('gm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search messages in group
     */
    public function searchGroupMessages(Group $group, string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.group = :group')
            ->andWhere('gm.content LIKE :query')
            ->andWhere('gm.deletedAt IS NULL')
            ->setParameter('group', $group)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('gm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get unread messages count for user in group
     */
    public function getUnreadCount(Group $group, User $user): int
    {
        return $this->createQueryBuilder('gm')
            ->select('COUNT(gm.id)')
            ->where('gm.group = :group')
            ->andWhere('gm.sender != :user')
            ->andWhere('gm.deletedAt IS NULL')
            ->setParameter('group', $group)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get system messages for group
     */
    public function getSystemMessages(Group $group, int $limit = 20): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.group = :group')
            ->andWhere('gm.isSystemMessage = true')
            ->andWhere('gm.deletedAt IS NULL')
            ->setParameter('group', $group)
            ->orderBy('gm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get messages with reactions
     */
    public function getMessagesWithReactions(Group $group, int $limit = 20): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.group = :group')
            ->andWhere('gm.reactions IS NOT NULL')
            ->andWhere('JSON_LENGTH(gm.reactions) > 0')
            ->andWhere('gm.deletedAt IS NULL')
            ->setParameter('group', $group)
            ->orderBy('gm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent messages for user's groups
     */
    public function getRecentMessagesForUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('gm')
            ->join('gm.group', 'g')
            ->join('g.members', 'm')
            ->where('m.user = :user')
            ->andWhere('m.isActive = true')
            ->andWhere('gm.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('gm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

