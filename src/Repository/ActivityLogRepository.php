<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    /**
     * Find activity logs with optional filters
     *
     * @return ActivityLog[]
     */
    public function findWithFilters(
        ?int $userId = null,
        ?string $action = null,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.created_at', 'DESC');

        if ($userId !== null) {
            $qb->andWhere('a.user_id = :userId')
               ->setParameter('userId', $userId);
        }

        if ($action !== null && $action !== '') {
            $qb->andWhere('a.action = :action')
               ->setParameter('action', $action);
        }

        if ($startDate !== null) {
            $qb->andWhere('a.created_at >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate !== null) {
            $qb->andWhere('a.created_at <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all distinct actions from logs
     *
     * @return array<string>
     */
    public function findDistinctActions(): array
    {
        $result = $this->createQueryBuilder('a')
            ->select('DISTINCT a.action')
            ->orderBy('a.action', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($result, 'action');
    }
}
