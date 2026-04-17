<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'e_ticket')]
#[ORM\UniqueConstraint(name: 'uniq_eticket_qr_token', columns: ['qr_token'])]
class ETicket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: BookingItem::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE', unique: true)]
    private BookingItem $bookingItem;

    #[ORM\Column(name: 'qr_token', length: 64)]
    #[Assert\NotBlank]
    private string $qrToken = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $filePath = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(BookingItem $bookingItem, string $qrToken, string $filePath)
    {
        $this->bookingItem = $bookingItem;
        $this->qrToken = $qrToken;
        $this->filePath = $filePath;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBookingItem(): BookingItem
    {
        return $this->bookingItem;
    }

    public function getQrToken(): string
    {
        return $this->qrToken;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
