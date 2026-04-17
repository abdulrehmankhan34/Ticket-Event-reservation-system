<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\BookingItem;
use App\Entity\SeatReservation;
use App\Entity\TicketTier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TicketTier>
 */
class TicketTierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketTier::class);
    }

    /**
     * @param list<int> $eventIds
     * @return list<TicketTier>
     */
    public function findByEventIds(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        /** @var list<TicketTier> $rows */
        $rows = $this->createQueryBuilder('t')
            ->andWhere('IDENTITY(t.event) IN (:eventIds)')
            ->setParameter('eventIds', $eventIds)
            ->orderBy('t.basePrice', 'ASC')
            ->getQuery()
            ->getResult();

        return $rows;
    }

    /**
     * Confirmed quantity per tier (from BookingItems).
     *
     * @param list<int> $tierIds
     * @return array<int,int> map tierId => confirmedQty
     */
    public function getConfirmedQuantityByTierIds(array $tierIds): array
    {
        if ($tierIds === []) {
            return [];
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('IDENTITY(bi.tier) AS tierId, COALESCE(SUM(bi.quantity), 0) AS qty')
            ->from(BookingItem::class, 'bi')
            ->innerJoin('bi.booking', 'b')
            ->andWhere('b.status = :status')
            ->andWhere('IDENTITY(bi.tier) IN (:tierIds)')
            ->setParameter('status', Booking::STATUS_CONFIRMED)
            ->setParameter('tierIds', $tierIds)
            ->groupBy('tierId');

        $rows = $qb->getQuery()->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['tierId']] = (int) $row['qty'];
        }

        return $out;
    }

    /**
     * Active reservations per tier (pending + not expired).
     *
     * @param list<int> $tierIds
     * @return array<int,int> map tierId => reservedQty
     */
    public function getActiveReservedQuantityByTierIds(array $tierIds, \DateTimeImmutable $now = new \DateTimeImmutable()): array
    {
        if ($tierIds === []) {
            return [];
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('IDENTITY(r.tier) AS tierId, COALESCE(SUM(r.quantity), 0) AS qty')
            ->from(SeatReservation::class, 'r')
            ->andWhere('r.status = :status')
            ->andWhere('r.expiresAt > :now')
            ->andWhere('IDENTITY(r.tier) IN (:tierIds)')
            ->setParameter('status', SeatReservation::STATUS_PENDING)
            ->setParameter('now', $now)
            ->setParameter('tierIds', $tierIds)
            ->groupBy('tierId');

        $rows = $qb->getQuery()->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['tierId']] = (int) $row['qty'];
        }

        return $out;
    }
}

