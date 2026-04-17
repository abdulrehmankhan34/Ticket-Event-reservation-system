<?php

namespace App\Repository;

use App\Entity\OrganizerProfile;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganizerProfile>
 */
class OrganizerProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizerProfile::class);
    }

    public function findOneByUser(User $user): ?OrganizerProfile
    {
        return $this->findOneBy(['user' => $user]);
    }

    /**
     * @return list<OrganizerProfile>
     */
    public function findPending(): array
    {
        /** @var list<OrganizerProfile> $rows */
        $rows = $this->createQueryBuilder('op')
            ->andWhere('op.approvalStatus = :status')
            ->setParameter('status', OrganizerProfile::STATUS_PENDING)
            ->orderBy('op.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $rows;
    }
}

