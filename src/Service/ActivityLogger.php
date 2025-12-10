<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ActivityLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function log(
        string $action,
        User $user,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null
    ): void {
        $activityLog = new ActivityLog();
        $activityLog->setUserId($user->getId());
        $activityLog->setUsername($user->getUsername() ?: $user->getEmail());
        $activityLog->setRole($user->getRoles());
        $activityLog->setAction($action);
        $activityLog->setEntityType($entityType);
        $activityLog->setEntityId($entityId);
        $activityLog->setDescription($description);

        $this->entityManager->persist($activityLog);
        $this->entityManager->flush();
    }
}
