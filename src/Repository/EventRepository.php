<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @param array{
     *   q?: string,
     *   category?: string,
     *   date_from?: string,
     *   date_to?: string,
     *   online?: string
     * } $filters
     *
     * @return list<Event>
     */
    public function findUpcomingWithFilters(array $filters, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('e');
        $qb->andWhere('e.startsAt >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.startsAt', 'ASC')
            ->setMaxResults($limit);

        // Hide cancelled events from catalog (still visible later in booking views).
        $qb->andWhere('e.status != :cancelled')
            ->setParameter('cancelled', Event::STATUS_CANCELLED);

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $qb->andWhere('LOWER(e.name) LIKE :q OR LOWER(e.description) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $qb->andWhere('e.category = :category')
                ->setParameter('category', $category);
        }

        $online = (string) ($filters['online'] ?? '');
        if ($online === '1') {
            $qb->andWhere('e.isOnline = true');
        } elseif ($online === '0') {
            $qb->andWhere('e.isOnline = false');
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            try {
                $qb->andWhere('e.startsAt >= :dateFrom')
                    ->setParameter('dateFrom', new \DateTimeImmutable($dateFrom));
            } catch (\Exception) {
                // ignore invalid date input
            }
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            try {
                $qb->andWhere('e.startsAt <= :dateTo')
                    ->setParameter('dateTo', new \DateTimeImmutable($dateTo));
            } catch (\Exception) {
                // ignore invalid date input
            }
        }

        /** @var list<Event> $rows */
        $rows = $qb->getQuery()->getResult();
        return $rows;
    }
}

