<?php

namespace App\Service;

use App\Entity\SeatReservation;
use App\Entity\TicketTier;
use App\Entity\User;
use App\Repository\SeatReservationRepository;
use App\Repository\TicketTierRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class ReservationService
{
    public const HOLD_MINUTES = 10;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SeatReservationRepository $reservations,
        private readonly TicketTierRepository $tiers,
    ) {
    }

    public function reserve(User $user, TicketTier $tier, int $quantity): SeatReservation
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive.');
        }

        return $this->em->wrapInTransaction(function () use ($user, $tier, $quantity) {
            // Pessimistically lock tier row while checking availability (soft lock).
            $this->em->lock($tier, LockMode::PESSIMISTIC_WRITE);

            $now = new \DateTimeImmutable();
            $saleStartsAt = $tier->getSaleStartsAt();
            $saleEndsAt = $tier->getSaleEndsAt();
            if ($saleStartsAt && $now < $saleStartsAt) {
                throw new \RuntimeException('This tier is not on sale yet.');
            }
            if ($saleEndsAt && $now > $saleEndsAt) {
                throw new \RuntimeException('This tier sale has ended.');
            }

            $tierId = (int) $tier->getId();
            $held = $this->tiers->getActiveReservedQuantityByTierIds([$tierId], $now)[$tierId] ?? 0;

            $remaining = max(0, $tier->getTotalSeats() - $tier->getSoldCount() - (int) $held);
            if ($quantity > $remaining) {
                throw new \RuntimeException('Sorry, this tier just sold out (or does not have enough seats).');
            }

            $reservedAt = $now;
            $expiresAt = $now->modify('+'.self::HOLD_MINUTES.' minutes');
            $reservation = new SeatReservation($user, $tier, $quantity, $reservedAt, $expiresAt);

            $this->em->persist($reservation);
            $this->em->flush();

            return $reservation;
        });
    }
}

