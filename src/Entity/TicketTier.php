<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'ticket_tier')]
class TicketTier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Event $event;

    #[ORM\Column(length: 64)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $basePrice = 0;

    #[ORM\Column]
    #[Assert\Positive]
    private int $totalSeats = 1;

    #[ORM\Column(options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private int $soldCount = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $saleStartsAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $saleEndsAt = null;

    /**
     * Optimistic lock version (core concurrency requirement).
     */
    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getBasePrice(): int
    {
        return $this->basePrice;
    }

    public function setBasePrice(int $basePrice): self
    {
        $this->basePrice = $basePrice;

        return $this;
    }

    public function getTotalSeats(): int
    {
        return $this->totalSeats;
    }

    public function setTotalSeats(int $totalSeats): self
    {
        $this->totalSeats = $totalSeats;

        return $this;
    }

    public function getSoldCount(): int
    {
        return $this->soldCount;
    }

    public function setSoldCount(int $soldCount): self
    {
        $this->soldCount = $soldCount;

        return $this;
    }

    public function getSaleStartsAt(): ?\DateTimeImmutable
    {
        return $this->saleStartsAt;
    }

    public function setSaleStartsAt(?\DateTimeImmutable $saleStartsAt): self
    {
        $this->saleStartsAt = $saleStartsAt;

        return $this;
    }

    public function getSaleEndsAt(): ?\DateTimeImmutable
    {
        return $this->saleEndsAt;
    }

    public function setSaleEndsAt(?\DateTimeImmutable $saleEndsAt): self
    {
        $this->saleEndsAt = $saleEndsAt;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}

