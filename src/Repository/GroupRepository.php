<?php

namespace App\Repository;

use App\Entity\Group;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Group>
 */
class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    /**
     * Get groups where user is a member
     */
    public function getUserGroups(User $user): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.members', 'm')
            ->where('m.user = :user')
            ->andWhere('m.isActive = true')
            ->andWhere('g.isActive = true')
            ->setParameter('user', $user)
            ->orderBy('g.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get public groups
     */
    public function getPublicGroups(int $limit = 20): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.isPublic = true')
            ->andWhere('g.isActive = true')
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find group by invite code
     */
    public function findByInviteCode(string $inviteCode): ?Group
    {
        return $this->createQueryBuilder('g')
            ->where('g.inviteCode = :inviteCode')
            ->andWhere('g.isActive = true')
            ->andWhere('g.inviteExpiresAt > :now')
            ->setParameter('inviteCode', $inviteCode)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Search groups by name
     */
    public function searchGroups(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.name LIKE :query')
            ->andWhere('g.isPublic = true')
            ->andWhere('g.isActive = true')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get groups owned by user
     */
    public function getOwnedGroups(User $user): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.owner = :user')
            ->andWhere('g.isActive = true')
            ->setParameter('user', $user)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get groups where user is moderator
     */
    public function getModeratedGroups(User $user): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.members', 'm')
            ->where('m.user = :user')
            ->andWhere('m.role IN (:roles)')
            ->andWhere('m.isActive = true')
            ->andWhere('g.isActive = true')
            ->setParameter('user', $user)
            ->setParameter('roles', [GroupMember::ROLE_OWNER, GroupMember::ROLE_MODERATOR])
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

