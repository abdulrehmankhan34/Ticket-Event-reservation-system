<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\BookingItem;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BookingItem>
 */
class BookingItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookingItem::class);
    }

    /**
     * @return list<BookingItem>
     */
    public function findByBooking(Booking $booking): array
    {
        /** @var list<BookingItem> $rows */
        $rows = $this->createQueryBuilder('bi')
            ->andWhere('bi.booking = :booking')
            ->setParameter('booking', $booking)
            ->orderBy('bi.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $rows;
    }

    public function countTotalTicketsSold(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COALESCE(SUM(bi.quantity), 0)')
            ->from(BookingItem::class, 'bi')
            ->innerJoin('bi.booking', 'b')
            ->andWhere('b.status = :status')
            ->setParameter('status', Booking::STATUS_CONFIRMED);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function sumSystemFees(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COALESCE(SUM(bi.systemFee * bi.quantity), 0)')
            ->from(BookingItem::class, 'bi')
            ->innerJoin('bi.booking', 'b')
            ->andWhere('b.status = :status')
            ->setParameter('status', Booking::STATUS_CONFIRMED);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Revenue + booking stats for a single event (confirmed bookings only).
     *
     * Gross = base price sum (organizer share)
     * Fee = 1% system fee sum
     * Final = gross + fee
     *
     * @return array{
     *   bookingCount:int,
     *   ticketsSold:int,
     *   gross:int,
     *   systemFee:int,
     *   final:int
     * }
     */
    public function getConfirmedStatsForEvent(Event $event): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select([
                'COUNT(DISTINCT b.id) AS bookingCount',
                'COALESCE(SUM(bi.quantity), 0) AS ticketsSold',
                'COALESCE(SUM(bi.unitBasePrice * bi.quantity), 0) AS gross',
                'COALESCE(SUM(bi.systemFee * bi.quantity), 0) AS systemFee',
                'COALESCE(SUM(bi.unitFinalPrice * bi.quantity), 0) AS final',
            ])
            ->from(BookingItem::class, 'bi')
            ->innerJoin('bi.booking', 'b')
            ->andWhere('b.event = :event')
            ->andWhere('b.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', Booking::STATUS_CONFIRMED);

        /** @var array{bookingCount:string|int|null,ticketsSold:string|int|null,gross:string|int|null,systemFee:string|int|null,final:string|int|null} $row */
        $row = $qb->getQuery()->getSingleResult();

        return [
            'bookingCount' => (int) ($row['bookingCount'] ?? 0),
            'ticketsSold' => (int) ($row['ticketsSold'] ?? 0),
            'gross' => (int) ($row['gross'] ?? 0),
            'systemFee' => (int) ($row['systemFee'] ?? 0),
            'final' => (int) ($row['final'] ?? 0),
        ];
    }
}

