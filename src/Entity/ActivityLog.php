<?php

namespace App\Entity;

use App\Repository\ActivityLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['activity_log:read']],
    paginationEnabled: true,
    paginationItemsPerPage: 25
)]
#[ApiFilter(SearchFilter::class, properties: ['username' => 'partial', 'action' => 'exact', 'entity_type' => 'exact'])]
#[ApiFilter(DateFilter::class, properties: ['created_at'])]
#[ApiFilter(OrderFilter::class, properties: ['id', 'created_at', 'action', 'username'])]
#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['activity_log:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['activity_log:read'])]
    private ?int $user_id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['activity_log:read'])]
    private ?string $username = null;

    #[ORM\Column]
    #[Groups(['activity_log:read'])]
    private array $role = [];

    #[ORM\Column(length: 255)]
    #[Groups(['activity_log:read'])]
    private ?string $action = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['activity_log:read'])]
    private ?string $entity_type = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['activity_log:read'])]
    private ?int $entity_id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['activity_log:read'])]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['activity_log:read'])]
    private ?\DateTimeImmutable $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getRole(): array
    {
        return $this->role;
    }

    public function setRole(array $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getEntityType(): ?string
    {
        return $this->entity_type;
    }

    public function setEntityType(?string $entity_type): static
    {
        $this->entity_type = $entity_type;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entity_id;
    }

    public function setEntityId(?int $entity_id): static
    {
        $this->entity_id = $entity_id;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtOnPrePersist(): void
    {
        if ($this->created_at === null) {
            $this->created_at = new \DateTimeImmutable();
        }
    }
}
