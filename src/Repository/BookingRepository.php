<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * @return list<Booking>
     */
    public function findConfirmedByEvent(Event $event): array
    {
        /** @var list<Booking> $rows */
        $rows = $this->createQueryBuilder('b')
            ->andWhere('b.event = :event')
            ->andWhere('b.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', Booking::STATUS_CONFIRMED)
            ->orderBy('b.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $rows;
    }
}

