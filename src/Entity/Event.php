<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'event')]
class Event
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_POSTPONED = 'postponed';
    public const STATUS_COMPLETED = 'completed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: OrganizerProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private OrganizerProfile $organizer;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $description = '';

    #[ORM\Column(length: 64)]
    #[Assert\NotBlank]
    private string $category = '';

    #[ORM\Column]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(length: 64, options: ['default' => 'UTC'])]
    #[Assert\NotBlank]
    private string $timezone = 'UTC';

    #[ORM\Column(options: ['default' => false])]
    private bool $isOnline = false;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $venueName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $venueAddress = null;

    #[ORM\Column(length: 16, options: ['default' => self::STATUS_DRAFT])]
    #[Assert\Choice([self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_CANCELLED, self::STATUS_POSTPONED, self::STATUS_COMPLETED])]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bannerPath = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(OrganizerProfile $organizer, \DateTimeImmutable $startsAt)
    {
        $this->organizer = $organizer;
        $this->startsAt = $startsAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganizer(): OrganizerProfile
    {
        return $this->organizer;
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): self
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function isOnline(): bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): self
    {
        $this->isOnline = $isOnline;

        return $this;
    }

    public function getVenueName(): ?string
    {
        return $this->venueName;
    }

    public function setVenueName(?string $venueName): self
    {
        $this->venueName = $venueName;

        return $this;
    }

    public function getVenueAddress(): ?string
    {
        return $this->venueAddress;
    }

    public function setVenueAddress(?string $venueAddress): self
    {
        $this->venueAddress = $venueAddress;

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

    public function getBannerPath(): ?string
    {
        return $this->bannerPath;
    }

    public function setBannerPath(?string $bannerPath): self
    {
        $this->bannerPath = $bannerPath;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

