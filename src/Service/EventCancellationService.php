<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Event;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\BookingItemRepository;
use App\Repository\BookingRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class EventCancellationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BookingRepository $bookings,
        private readonly BookingItemRepository $bookingItems,
    ) {
    }

    /**
     * Cancel an event and refund all confirmed bookings (full credit refund).
     */
    public function cancelAndRefund(Event $event): int
    {
        return $this->em->wrapInTransaction(function () use ($event) {
            if ($event->getStatus() === Event::STATUS_CANCELLED) {
                return 0;
            }

            $confirmed = $this->bookings->findConfirmedByEvent($event);
            $refundedCount = 0;

            foreach ($confirmed as $booking) {
                $user = $booking->getUser();

                // Lock each user row before updating credit balance.
                $this->em->lock($user, LockMode::PESSIMISTIC_WRITE);

                // Reconcile inventory: roll back soldCount for tiers in this booking.
                // soldCount is used as a fast counter elsewhere; if we refund a confirmed booking,
                // we must decrement it to avoid "phantom sold out" after refunds.
                $items = $this->bookingItems->findByBooking($booking);
                foreach ($items as $item) {
                    $tier = $item->getTier();
                    $this->em->lock($tier, LockMode::PESSIMISTIC_WRITE);

                    $newSold = max(0, $tier->getSoldCount() - $item->getQuantity());
                    $tier->setSoldCount($newSold);
                }

                $amount = $booking->getTotalCredits();
                $user->setCreditBalance($user->getCreditBalance() + $amount);
                $this->em->persist(new Transaction($user, $amount, Transaction::TYPE_REFUND, 'event_cancel:'.$event->getId().':booking:'.$booking->getId()));

                $booking->setStatus(Booking::STATUS_REFUNDED);
                $refundedCount++;
            }

            $event->setStatus(Event::STATUS_CANCELLED);

            $this->em->flush();

            return $refundedCount;
        });
    }
}

