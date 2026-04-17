<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'booking')]
#[ORM\UniqueConstraint(name: 'uniq_booking_idempotency_key', columns: ['idempotency_key'])]
class Booking
{
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Event $event;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $totalCredits = 0;

    #[ORM\Column(length: 16, options: ['default' => self::STATUS_CONFIRMED])]
    #[Assert\Choice([self::STATUS_CONFIRMED, self::STATUS_CANCELLED, self::STATUS_REFUNDED])]
    private string $status = self::STATUS_CONFIRMED;

    #[ORM\Column(name: 'idempotency_key', length: 64)]
    #[Assert\NotBlank]
    private string $idempotencyKey = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, Event $event, string $idempotencyKey)
    {
        $this->user = $user;
        $this->event = $event;
        $this->idempotencyKey = $idempotencyKey;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function getTotalCredits(): int
    {
        return $this->totalCredits;
    }

    public function setTotalCredits(int $totalCredits): self
    {
        $this->totalCredits = $totalCredits;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
