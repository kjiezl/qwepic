<?php

namespace App\Entity;

use App\Repository\PhotoRepository;
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
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_PHOTOGRAPHER')"),
        new Put(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_PHOTOGRAPHER') and object.getAlbum() and object.getAlbum().getPhotographer() == user)"),
        new Patch(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_PHOTOGRAPHER') and object.getAlbum() and object.getAlbum().getPhotographer() == user)"),
        new Delete(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_PHOTOGRAPHER') and object.getAlbum() and object.getAlbum().getPhotographer() == user)"),
    ],
    normalizationContext: ['groups' => ['photo:read']],
    denormalizationContext: ['groups' => ['photo:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 20
)]
#[ApiFilter(SearchFilter::class, properties: ['title' => 'partial', 'caption' => 'partial', 'album.title' => 'partial'])]
#[ApiFilter(BooleanFilter::class, properties: ['is_public'])]
#[ApiFilter(OrderFilter::class, properties: ['id', 'title', 'created_at', 'updated_at'])]
#[ORM\Entity(repositoryClass: PhotoRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Photo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['photo:read', 'booking:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Album::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['photo:read', 'photo:write'])]
    private ?Album $album = null;

    #[ORM\Column(length: 255)]
    #[Groups(['photo:read', 'photo:write'])]
    private ?string $storage_path = null;

    #[ORM\Column(length: 255)]
    #[Groups(['photo:read', 'photo:write'])]
    private ?string $thumbnail_path = null;

    #[ORM\Column(length: 255)]
    #[Groups(['photo:read', 'photo:write', 'booking:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['photo:read', 'photo:write'])]
    private ?string $caption = null;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['photo:read', 'photo:write'])]
    private ?bool $is_public = true;

    #[ORM\Column(nullable: true)]
    #[Groups(['photo:read'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['photo:read'])]
    private ?\DateTimeImmutable $updated_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function setAlbum(?Album $album): static
    {
        $this->album = $album;

        return $this;
    }

    public function getStoragePath(): ?string
    {
        return $this->storage_path;
    }

    public function setStoragePath(string $storage_path): static
    {
        $this->storage_path = $storage_path;

        return $this;
    }

    public function getThumbnailPath(): ?string
    {
        return $this->thumbnail_path;
    }

    public function setThumbnailPath(string $thumbnail_path): static
    {
        $this->thumbnail_path = $thumbnail_path;

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

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): static
    {
        $this->caption = $caption;

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
