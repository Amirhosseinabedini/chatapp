<?php

namespace App\Repository;

use App\Entity\Group;
use App\Entity\GroupMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupMember>
 */
class GroupMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupMember::class);
    }

    /**
     * Find member by group and user
     */
    public function findByGroupAndUser(Group $group, User $user): ?GroupMember
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.group = :group')
            ->andWhere('gm.user = :user')
            ->andWhere('gm.isActive = true')
            ->setParameter('group', $group)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get group members
     */
    public function getGroupMembers(Group $group): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.group = :group')
            ->andWhere('gm.isActive = true')
            ->setParameter('group', $group)
            ->orderBy('gm.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get group moderators
     */
    public function getGroupModerators(Group $group): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.group = :group')
            ->andWhere('gm.role IN (:roles)')
            ->andWhere('gm.isActive = true')
            ->setParameter('group', $group)
            ->setParameter('roles', [GroupMember::ROLE_OWNER, GroupMember::ROLE_MODERATOR])
            ->orderBy('gm.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get user's group memberships
     */
    public function getUserMemberships(User $user): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.user = :user')
            ->andWhere('gm.isActive = true')
            ->setParameter('user', $user)
            ->orderBy('gm.joinedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count group members
     */
    public function countGroupMembers(Group $group): int
    {
        return $this->createQueryBuilder('gm')
            ->select('COUNT(gm.id)')
            ->where('gm.group = :group')
            ->andWhere('gm.isActive = true')
            ->setParameter('group', $group)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Remove user from group
     */
    public function removeUserFromGroup(Group $group, User $user): bool
    {
        $member = $this->findByGroupAndUser($group, $user);
        if ($member) {
            $member->setIsActive(false);
            $this->getEntityManager()->flush();
            return true;
        }
        return false;
    }

    /**
     * Update user role in group
     */
    public function updateUserRole(Group $group, User $user, string $role): bool
    {
        $member = $this->findByGroupAndUser($group, $user);
        if ($member) {
            $member->setRole($role);
            $this->getEntityManager()->flush();
            return true;
        }
        return false;
    }
}

