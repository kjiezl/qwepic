<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Assert\Callback('validate')]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $client = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $photographer = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['requested', 'accepted', 'rejected', 'completed', 'cancelled'])]
    #[Assert\Length(max: 30)]
    private ?string $status = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $endAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $location = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 5000)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000)]
    private ?string $rejectionReason = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;

        return $this;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;

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

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function validate(ExecutionContextInterface $context): void
    {
        if ($this->startAt !== null && $this->endAt !== null) {
            if ($this->endAt <= $this->startAt) {
                $context->buildViolation('End date/time must be after the start date/time.')
                    ->atPath('endAt')
                    ->addViolation();
            }
        }

        if ($this->status === 'requested' && $this->startAt !== null) {
            $now = new \DateTimeImmutable();
            if ($this->startAt < $now) {
                $context->buildViolation('Start date/time cannot be in the past.')
                    ->atPath('startAt')
                    ->addViolation();
            }
        }

        if ($this->status === 'rejected') {
            $reason = $this->rejectionReason;
            if ($reason === null || trim($reason) === '') {
                $context->buildViolation('Rejection reason is required when a booking is rejected.')
                    ->atPath('rejectionReason')
                    ->addViolation();
            }
        }
    }

    #[ORM\PrePersist]
    public function setTimestampsOnPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        if ($this->createdAt === null) {
            $this->createdAt = $now;
        }

        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtOnPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
