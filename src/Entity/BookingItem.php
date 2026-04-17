<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'booking_item')]
class BookingItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Booking::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Booking $booking;

    #[ORM\ManyToOne(targetEntity: TicketTier::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private TicketTier $tier;

    #[ORM\Column]
    #[Assert\Positive]
    private int $quantity = 1;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $unitBasePrice = 0;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $unitFinalPrice = 0;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $systemFee = 0;

    public function __construct(Booking $booking, TicketTier $tier)
    {
        $this->booking = $booking;
        $this->tier = $tier;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBooking(): Booking
    {
        return $this->booking;
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

    public function getUnitBasePrice(): int
    {
        return $this->unitBasePrice;
    }

    public function setUnitBasePrice(int $unitBasePrice): self
    {
        $this->unitBasePrice = $unitBasePrice;

        return $this;
    }

    public function getUnitFinalPrice(): int
    {
        return $this->unitFinalPrice;
    }

    public function setUnitFinalPrice(int $unitFinalPrice): self
    {
        $this->unitFinalPrice = $unitFinalPrice;

        return $this;
    }

    public function getSystemFee(): int
    {
        return $this->systemFee;
    }

    public function setSystemFee(int $systemFee): self
    {
        $this->systemFee = $systemFee;

        return $this;
    }
}
