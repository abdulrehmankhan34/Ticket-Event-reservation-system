<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'transaction')]
#[ORM\Index(name: 'idx_transaction_user_created_at', columns: ['user_id', 'created_at'])]
class Transaction
{
    public const TYPE_DEBIT = 'debit';
    public const TYPE_CREDIT = 'credit';
    public const TYPE_REFUND = 'refund';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private User $user;

    #[ORM\Column]
    private int $amount = 0;

    #[ORM\Column(length: 16)]
    #[Assert\Choice([self::TYPE_DEBIT, self::TYPE_CREDIT, self::TYPE_REFUND])]
    private string $type = self::TYPE_DEBIT;

    #[ORM\Column(length: 128)]
    #[Assert\NotBlank]
    private string $reference = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, int $amount, string $type, string $reference)
    {
        $this->user = $user;
        $this->amount = $amount;
        $this->type = $type;
        $this->reference = $reference;
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

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
