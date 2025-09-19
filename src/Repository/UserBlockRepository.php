<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserBlock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBlock>
 */
class UserBlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBlock::class);
    }

    /**
     * Check if user is blocked by another user
     */
    public function isUserBlocked(User $blocker, User $blocked): bool
    {
        $block = $this->createQueryBuilder('ub')
            ->where('ub.blocker = :blocker')
            ->andWhere('ub.blocked = :blocked')
            ->andWhere('ub.isActive = true')
            ->setParameter('blocker', $blocker)
            ->setParameter('blocked', $blocked)
            ->getQuery()
            ->getOneOrNullResult();

        return $block !== null;
    }

    /**
     * Get users blocked by a user
     */
    public function getBlockedUsers(User $user): array
    {
        return $this->createQueryBuilder('ub')
            ->where('ub.blocker = :user')
            ->andWhere('ub.isActive = true')
            ->setParameter('user', $user)
            ->orderBy('ub.blockedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get users who blocked a user
     */
    public function getBlockers(User $user): array
    {
        return $this->createQueryBuilder('ub')
            ->where('ub.blocked = :user')
            ->andWhere('ub.isActive = true')
            ->setParameter('user', $user)
            ->orderBy('ub.blockedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Block a user
     */
    public function blockUser(User $blocker, User $blocked, ?string $reason = null): UserBlock
    {
        $block = new UserBlock();
        $block->setBlocker($blocker);
        $block->setBlocked($blocked);
        $block->setReason($reason);

        $this->getEntityManager()->persist($block);
        $this->getEntityManager()->flush();

        return $block;
    }

    /**
     * Unblock a user
     */
    public function unblockUser(User $blocker, User $blocked): bool
    {
        $block = $this->createQueryBuilder('ub')
            ->where('ub.blocker = :blocker')
            ->andWhere('ub.blocked = :blocked')
            ->andWhere('ub.isActive = true')
            ->setParameter('blocker', $blocker)
            ->setParameter('blocked', $blocked)
            ->getQuery()
            ->getOneOrNullResult();

        if ($block) {
            $block->setIsActive(false);
            $this->getEntityManager()->flush();
            return true;
        }

        return false;
    }

    /**
     * Get mutual blocks between two users
     */
    public function getMutualBlocks(User $user1, User $user2): array
    {
        return $this->createQueryBuilder('ub')
            ->where('(ub.blocker = :user1 AND ub.blocked = :user2) OR (ub.blocker = :user2 AND ub.blocked = :user1)')
            ->andWhere('ub.isActive = true')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->getQuery()
            ->getResult();
    }
}

