<?php

namespace App\Entity;

use App\Repository\GroupMemberRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupMemberRepository::class)]
#[ORM\Table(name: 'group_member')]
#[ORM\UniqueConstraint(name: 'UNIQ_GROUP_USER', fields: ['group', 'user'])]
class GroupMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Group $group = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private ?string $role = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $joinedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastReadAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isMuted = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    public const ROLE_OWNER = 'owner';
    public const ROLE_MODERATOR = 'moderator';
    public const ROLE_MEMBER = 'member';

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
        $this->role = self::ROLE_MEMBER;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): static
    {
        $this->group = $group;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;
        return $this;
    }

    public function getLastReadAt(): ?\DateTimeImmutable
    {
        return $this->lastReadAt;
    }

    public function setLastReadAt(?\DateTimeImmutable $lastReadAt): static
    {
        $this->lastReadAt = $lastReadAt;
        return $this;
    }

    public function isMuted(): bool
    {
        return $this->isMuted;
    }

    public function setIsMuted(bool $isMuted): static
    {
        $this->isMuted = $isMuted;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isModerator(): bool
    {
        return $this->role === self::ROLE_MODERATOR;
    }

    public function isMember(): bool
    {
        return $this->role === self::ROLE_MEMBER;
    }

    public function canManageMembers(): bool
    {
        return $this->isOwner() || $this->isModerator();
    }

    public function canManageGroup(): bool
    {
        return $this->isOwner();
    }

    public function canDeleteMessages(): bool
    {
        return $this->isOwner() || $this->isModerator();
    }

    public function canPinMessages(): bool
    {
        return $this->isOwner() || $this->isModerator();
    }
}
