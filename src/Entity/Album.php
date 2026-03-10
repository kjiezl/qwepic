<?php

namespace App\Entity;

use App\Repository\AlbumRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_PHOTOGRAPHER')"),
        new Put(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_PHOTOGRAPHER') and object.getPhotographer() == user)"),
        new Patch(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_PHOTOGRAPHER') and object.getPhotographer() == user)"),
        new Delete(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_PHOTOGRAPHER') and object.getPhotographer() == user)"),
    ],
    normalizationContext: ['groups' => ['album:read']],
    denormalizationContext: ['groups' => ['album:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 20
)]
#[ApiFilter(SearchFilter::class, properties: ['title' => 'partial', 'description' => 'partial', 'photographer.username' => 'partial'])]
#[ApiFilter(BooleanFilter::class, properties: ['is_public'])]
#[ApiFilter(OrderFilter::class, properties: ['id', 'title', 'created_at', 'updated_at'])]
#[ORM\Entity(repositoryClass: AlbumRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Album
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['album:read', 'photo:read', 'booking:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['album:read', 'album:write'])]
    private ?User $photographer = null;

    #[ORM\Column(length: 255)]
    #[Groups(['album:read', 'album:write', 'photo:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['album:read', 'album:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['album:read', 'album:write'])]
    private ?string $cover_image_path = null;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['album:read', 'album:write'])]
    private ?bool $is_public = true;

    #[ORM\Column(nullable: true)]
    #[Groups(['album:read'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['album:read'])]
    private ?\DateTimeImmutable $updated_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhotographer(): ?User
    {
        return $this->photographer;
    }

    public function setPhotographer(?User $photographer): static
    {
        $this->photographer = $photographer;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getCoverImagePath(): ?string
    {
        return $this->cover_image_path;
    }

    public function setCoverImagePath(?string $cover_image_path): static
    {
        $this->cover_image_path = $cover_image_path;

        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->is_public;
    }

    public function setIsPublic(bool $is_public): static
    {
        $this->is_public = $is_public;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    #[ORM\PrePersist]
    public function setTimestampsOnPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        if ($this->created_at === null) {
            $this->created_at = $now;
        }

        $this->updated_at = $now;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtOnPreUpdate(): void
    {
        $this->updated_at = new \DateTimeImmutable();
    }
}
