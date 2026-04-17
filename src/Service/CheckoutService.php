<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\BookingItem;
use App\Entity\SeatReservation;
use App\Entity\TicketTier;
use App\Entity\Transaction;
use App\Entity\User;
use App\Message\GenerateEticketMessage;
use App\Repository\SeatReservationRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Messenger\MessageBusInterface;

final class CheckoutService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SeatReservationRepository $reservations,
        private readonly Pricing $pricing,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function checkoutUserPendingReservations(User $user, string $idempotencyKey): Booking
    {
        return $this->em->wrapInTransaction(function () use ($user, $idempotencyKey) {
            // Idempotency: if already processed, return existing booking.
            $existing = $this->em->getRepository(Booking::class)->findOneBy(['idempotencyKey' => $idempotencyKey]);
            if ($existing instanceof Booking) {
                return $existing;
            }

            // Lock user row for atomic credit deduction.
            $this->em->lock($user, LockMode::PESSIMISTIC_WRITE);

            $now = new \DateTimeImmutable();
            $items = $this->reservations->findActiveForUser($user, $now);
            if ($items === []) {
                throw new \RuntimeException('Your cart is empty or reservations expired.');
            }

            // All reservations must belong to same event? For now we allow multi-event by creating booking per event later.
            // To keep it simple, enforce single-event checkout.
            $event = $items[0]->getTier()->getEvent();
            foreach ($items as $r) {
                if ($r->getTier()->getEvent()->getId() !== $event->getId()) {
                    throw new \RuntimeException('Please checkout one event at a time.');
                }
            }

            $total = 0;
            foreach ($items as $r) {
                $tier = $r->getTier();

                // Sale window validation (server time).
                if ($tier->getSaleStartsAt() && $now < $tier->getSaleStartsAt()) {
                    throw new \RuntimeException('A tier in your cart is not on sale yet.');
                }
                if ($tier->getSaleEndsAt() && $now > $tier->getSaleEndsAt()) {
                    throw new \RuntimeException('A tier in your cart has ended sale.');
                }

                $total += $this->pricing->finalPrice($tier->getBasePrice()) * $r->getQuantity();
            }

            if ($user->getCreditBalance() < $total) {
                throw new \RuntimeException('Insufficient credits.');
            }

            // Create booking first.
            $booking = new Booking($user, $event, $idempotencyKey);
            $booking->setTotalCredits($total);
            $this->em->persist($booking);

            // Confirm reservations and increment soldCount with optimistic locking.
            foreach ($items as $r) {
                $tier = $r->getTier();

                try {
                    // Optimistic lock: will throw if concurrent update to tier happened.
                    $this->em->lock($tier, LockMode::OPTIMISTIC, $tier->getVersion());
                } catch (OptimisticLockException $e) {
                    throw new \RuntimeException('Sorry, this tier just sold out. Please try again.');
                }

                // Ensure reservation still valid
                if (!$r->isActive($now)) {
                    throw new \RuntimeException('A reservation expired during checkout.');
                }

                $remaining = $tier->getTotalSeats() - $tier->getSoldCount();
                if ($r->getQuantity() > $remaining) {
                    throw new \RuntimeException('Sorry, this tier just sold out.');
                }

                $tier->setSoldCount($tier->getSoldCount() + $r->getQuantity());
                $r->setStatus(SeatReservation::STATUS_CONFIRMED);

                $bi = new BookingItem($booking, $tier);
                $bi->setQuantity($r->getQuantity());
                $bi->setUnitBasePrice($tier->getBasePrice());
                $bi->setUnitFinalPrice($this->pricing->finalPrice($tier->getBasePrice()));
                $bi->setSystemFee($this->pricing->systemFee($tier->getBasePrice()));
                $this->em->persist($bi);
            }

            // Deduct credits + transaction record.
            $user->setCreditBalance($user->getCreditBalance() - $total);
            $this->em->persist(new Transaction($user, -$total, Transaction::TYPE_DEBIT, 'booking:'.$idempotencyKey));

            $this->em->flush();

            // Dispatch async e-ticket generation per booking item.
            // (Messages are stored in messenger_messages table with doctrine transport.)
            $bookingItems = $this->em->getRepository(BookingItem::class)->findBy(['booking' => $booking]);
            foreach ($bookingItems as $bi) {
                $this->bus->dispatch(new GenerateEticketMessage((int) $bi->getId()));
            }

            return $booking;
        });
    }
}

