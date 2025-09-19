<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPublic = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $inviteCode = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $inviteExpiresAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    /**
     * @var Collection<int, GroupMember>
     */
    #[ORM\OneToMany(targetEntity: GroupMember::class, mappedBy: 'group', cascade: ['persist', 'remove'])]
    private Collection $members;

    /**
     * @var Collection<int, GroupMessage>
     */
    #[ORM\OneToMany(targetEntity: GroupMessage::class, mappedBy: 'group', cascade: ['persist', 'remove'])]
    private Collection $messages;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function getInviteCode(): ?string
    {
        return $this->inviteCode;
    }

    public function setInviteCode(?string $inviteCode): static
    {
        $this->inviteCode = $inviteCode;
        return $this;
    }

    public function getInviteExpiresAt(): ?\DateTimeImmutable
    {
        return $this->inviteExpiresAt;
    }

    public function setInviteExpiresAt(?\DateTimeImmutable $inviteExpiresAt): static
    {
        $this->inviteExpiresAt = $inviteExpiresAt;
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

    /**
     * @return Collection<int, GroupMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(GroupMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setGroup($this);
        }
        return $this;
    }

    public function removeMember(GroupMember $member): static
    {
        if ($this->members->removeElement($member)) {
            if ($member->getGroup() === $this) {
                $member->setGroup(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, GroupMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(GroupMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setGroup($this);
        }
        return $this;
    }

    public function removeMessage(GroupMessage $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getGroup() === $this) {
                $message->setGroup(null);
            }
        }
        return $this;
    }

    public function generateInviteCode(): string
    {
        $this->inviteCode = bin2hex(random_bytes(16));
        $this->inviteExpiresAt = (new \DateTimeImmutable())->add(new \DateInterval('P7D')); // 7 days
        return $this->inviteCode;
    }

    public function isInviteValid(): bool
    {
        return $this->inviteCode !== null && 
               $this->inviteExpiresAt !== null && 
               $this->inviteExpiresAt > new \DateTimeImmutable();
    }

    public function getMemberCount(): int
    {
        return $this->members->count();
    }

    public function isUserMember(User $user): bool
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                return true;
            }
        }
        return false;
    }

    public function getUserRole(User $user): ?string
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                return $member->getRole();
            }
        }
        return null;
    }
}

