<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'seat_reservation')]
#[ORM\Index(name: 'idx_reservation_expires_at', columns: ['expires_at'])]
class SeatReservation
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: TicketTier::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private TicketTier $tier;

    #[ORM\Column]
    #[Assert\Positive]
    private int $quantity = 1;

    #[ORM\Column]
    private \DateTimeImmutable $reservedAt;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(length: 16, options: ['default' => self::STATUS_PENDING])]
    #[Assert\Choice([self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_EXPIRED])]
    private string $status = self::STATUS_PENDING;

    public function isActive(\DateTimeImmutable $now = new \DateTimeImmutable()): bool
    {
        return $this->status === self::STATUS_PENDING && $this->expiresAt > $now;
    }

    public function __construct(User $user, TicketTier $tier, int $quantity, \DateTimeImmutable $reservedAt, \DateTimeImmutable $expiresAt)
    {
        $this->user = $user;
        $this->tier = $tier;
        $this->quantity = $quantity;
        $this->reservedAt = $reservedAt;
        $this->expiresAt = $expiresAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTier(): TicketTier
    {
        return $this->tier;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getReservedAt(): \DateTimeImmutable
    {
        return $this->reservedAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
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
}
