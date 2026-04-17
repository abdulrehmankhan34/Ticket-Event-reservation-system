<?php

namespace App\Repository;

use App\Entity\SeatReservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SeatReservation>
 */
class SeatReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SeatReservation::class);
    }

    /**
     * @return list<SeatReservation>
     */
    public function findActiveForUser(User $user, \DateTimeImmutable $now = new \DateTimeImmutable()): array
    {
        /** @var list<SeatReservation> $rows */
        $rows = $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.status = :status')
            ->andWhere('r.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('status', SeatReservation::STATUS_PENDING)
            ->setParameter('now', $now)
            ->orderBy('r.reservedAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $rows;
    }

    public function expireStale(\DateTimeImmutable $now = new \DateTimeImmutable()): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update(SeatReservation::class, 'r')
            ->set('r.status', ':expired')
            ->andWhere('r.status = :pending')
            ->andWhere('r.expiresAt <= :now')
            ->setParameter('expired', SeatReservation::STATUS_EXPIRED)
            ->setParameter('pending', SeatReservation::STATUS_PENDING)
            ->setParameter('now', $now);

        return $qb->getQuery()->execute();
    }
}

