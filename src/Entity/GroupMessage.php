<?php

namespace App\Entity;

use App\Repository\GroupMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupMessageRepository::class)]
#[ORM\Table(name: 'group_message')]
#[ORM\Index(name: 'idx_group_message_group', columns: ['group_id'])]
#[ORM\Index(name: 'idx_group_message_sender', columns: ['sender_id'])]
#[ORM\Index(name: 'idx_group_message_created_at', columns: ['created_at'])]
#[ORM\HasLifecycleCallbacks]
class GroupMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Group $group = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sender = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $messageType = 'text';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $fileSize = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $fileType = null;

    #[ORM\ManyToOne(targetEntity: GroupMessage::class)]
    #[ORM\JoinColumn(name: 'reply_to_id', referencedColumnName: 'id')]
    private ?GroupMessage $replyTo = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPinned = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isEdited = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isSystemMessage = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $reactions = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $editHistory = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->reactions = [];
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

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
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

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getMessageType(): ?string
    {
        return $this->messageType;
    }

    public function setMessageType(?string $messageType): static
    {
        $this->messageType = $messageType;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): static
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getFileType(): ?string
    {
        return $this->fileType;
    }

    public function setFileType(?string $fileType): static
    {
        $this->fileType = $fileType;
        return $this;
    }

    public function getReplyTo(): ?GroupMessage
    {
        return $this->replyTo;
    }

    public function setReplyTo(?GroupMessage $replyTo): static
    {
        $this->replyTo = $replyTo;
        return $this;
    }

    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    public function setIsPinned(bool $isPinned): static
    {
        $this->isPinned = $isPinned;
        return $this;
    }

    public function isEdited(): bool
    {
        return $this->isEdited;
    }

    public function setIsEdited(bool $isEdited): static
    {
        $this->isEdited = $isEdited;
        return $this;
    }

    public function isSystemMessage(): bool
    {
        return $this->isSystemMessage;
    }

    public function setIsSystemMessage(bool $isSystemMessage): static
    {
        $this->isSystemMessage = $isSystemMessage;
        return $this;
    }

    public function getReactions(): ?array
    {
        return $this->reactions ?? [];
    }

    public function setReactions(?array $reactions): static
    {
        $this->reactions = $reactions;
        return $this;
    }

    public function getEditHistory(): ?array
    {
        return $this->editHistory ?? [];
    }

    public function setEditHistory(?array $editHistory): static
    {
        $this->editHistory = $editHistory;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function markAsDeleted(): static
    {
        $this->deletedAt = new \DateTimeImmutable();
        return $this;
    }

    public function editMessage(string $newContent): static
    {
        // Save edit history
        $editHistory = $this->getEditHistory();
        $editHistory[] = [
            'content' => $this->content,
            'edited_at' => $this->updatedAt?->format('Y-m-d H:i:s') ?? $this->createdAt->format('Y-m-d H:i:s'),
        ];
        
        $this->setEditHistory($editHistory);
        $this->setContent($newContent);
        $this->setUpdatedAt(new \DateTimeImmutable());
        $this->setIsEdited(true);
        
        return $this;
    }

    public function addReaction(string $emoji, User $user): static
    {
        $reactions = $this->getReactions();
        $userId = $user->getId();
        
        if (!isset($reactions[$emoji])) {
            $reactions[$emoji] = [];
        }
        
        if (!in_array($userId, $reactions[$emoji])) {
            $reactions[$emoji][] = $userId;
        }
        
        $this->setReactions($reactions);
        return $this;
    }

    public function removeReaction(string $emoji, User $user): static
    {
        $reactions = $this->getReactions();
        $userId = $user->getId();
        
        if (isset($reactions[$emoji])) {
            $reactions[$emoji] = array_filter($reactions[$emoji], fn($id) => $id !== $userId);
            if (empty($reactions[$emoji])) {
                unset($reactions[$emoji]);
            }
        }
        
        $this->setReactions($reactions);
        return $this;
    }

    public function getReactionCount(string $emoji): int
    {
        $reactions = $this->getReactions();
        return isset($reactions[$emoji]) ? count($reactions[$emoji]) : 0;
    }

    public function hasUserReacted(string $emoji, User $user): bool
    {
        $reactions = $this->getReactions();
        return isset($reactions[$emoji]) && in_array($user->getId(), $reactions[$emoji]);
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }
}

