<?php

namespace App\Entity;

use App\Repository\BookingAttachmentRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_ADMIN') or object.getBooking().getClient() == user or object.getBooking().getPhotographer() == user"),
        new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_PHOTOGRAPHER')"),
        new Delete(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_PHOTOGRAPHER') and object.getBooking().getPhotographer() == user)"),
    ],
    normalizationContext: ['groups' => ['booking_attachment:read']],
    denormalizationContext: ['groups' => ['booking_attachment:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 20
)]
#[ApiFilter(SearchFilter::class, properties: ['booking.id' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['id', 'createdAt'])]
#[ORM\Entity(repositoryClass: BookingAttachmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class BookingAttachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['booking_attachment:read', 'booking:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['booking_attachment:read', 'booking_attachment:write'])]
    private ?Booking $booking = null;

    #[ORM\ManyToOne]
    #[Groups(['booking_attachment:read', 'booking_attachment:write', 'booking:read'])]
    private ?Album $album = null;

    #[ORM\ManyToOne]
    #[Groups(['booking_attachment:read', 'booking_attachment:write', 'booking:read'])]
    private ?Photo $photo = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['booking_attachment:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        $this->booking = $booking;

        return $this;
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

    public function getPhoto(): ?Photo
    {
        return $this->photo;
    }

    public function setPhoto(?Photo $photo): static
    {
        $this->photo = $photo;

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

    #[ORM\PrePersist]
    public function setCreatedAtOnPrePersist(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }
}
