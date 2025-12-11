<?php

namespace App\EventSubscriber;

use App\Entity\Album;
use App\Entity\Photo;
use App\Entity\User;
use App\Service\ActivityLogger;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
class EntityActivitySubscriber
{
    public function __construct(
        private ActivityLogger $activityLogger,
        private Security $security,
    ) {
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $actor = $this->security->getUser();

        if (!$actor instanceof User) {
            return;
        }

        if ($entity instanceof User) {
            // Admin creates a user
            if ($this->security->isGranted('ROLE_ADMIN')) {
                $description = sprintf(
                    'Admin %s created user %s (ID %d).',
                    $actor->getUsername() ?: ($actor->getEmail() ?? 'unknown'),
                    $entity->getUsername() ?: ($entity->getEmail() ?? 'unknown'),
                    $entity->getId() ?? 0,
                );

                $this->activityLogger->log('CREATE', $actor, 'USER', $entity->getId(), $description);
            }
        } elseif ($entity instanceof Album) {
            $description = sprintf(
                '%s %s created album "%s" (ID %d).',
                $this->security->isGranted('ROLE_ADMIN') ? 'Admin' : 'Photographer',
                $actor->getUsername() ?: ($actor->getEmail() ?? 'unknown'),
                $entity->getTitle() ?? 'Untitled',
                $entity->getId() ?? 0,
            );

            $this->activityLogger->log('CREATE', $actor, 'ALBUM', $entity->getId(), $description);
        } elseif ($entity instanceof Photo) {
            $description = sprintf(
                '%s %s created photo "%s" (ID %d).',
                $this->security->isGranted('ROLE_ADMIN') ? 'Admin' : 'Photographer',
                $actor->getUsername() ?: ($actor->getEmail() ?? 'unknown'),
                $entity->getTitle() ?? 'Untitled',
                $entity->getId() ?? 0,
            );

            $this->activityLogger->log('CREATE', $actor, 'PHOTO', $entity->getId(), $description);
        }
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $actor = $this->security->getUser();

        if (!$actor instanceof User) {
            return;
        }

        if ($entity instanceof User) {
            $objectManager = $args->getObjectManager();
            if ($objectManager instanceof EntityManagerInterface) {
                $uow = $objectManager->getUnitOfWork();
                $changeSet = $uow->getEntityChangeSet($entity);

                if (!empty($changeSet)) {
                    $changedFields = array_keys($changeSet);

                    $allowedFields = ['is_active', 'updated_at'];
                    $onlyActiveToggled = in_array('is_active', $changedFields, true)
                        && array_diff($changedFields, $allowedFields) === [];

                    if ($onlyActiveToggled) {
                        return;
                    }
                }
            }

            if ($this->security->isGranted('ROLE_ADMIN')) {
                $description = sprintf(
                    'Admin %s updated user %s (ID %d).',
                    $actor->getUsername() ?: ($actor->getEmail() ?? 'unknown'),
                    $entity->getUsername() ?: ($entity->getEmail() ?? 'unknown'),
                    $entity->getId() ?? 0,
                );

                $this->activityLogger->log('UPDATE', $actor, 'USER', $entity->getId(), $description);
            }
        } elseif ($entity instanceof Album) {
            $description = sprintf(
                '%s %s updated album "%s" (ID %d).',
                $this->security->isGranted('ROLE_ADMIN') ? 'Admin' : 'Photographer',
                $actor->getUsername() ?: ($actor->getEmail() ?? 'unknown'),
                $entity->getTitle() ?? 'Untitled',
                $entity->getId() ?? 0,
            );

            $this->activityLogger->log('UPDATE', $actor, 'ALBUM', $entity->getId(), $description);
        } elseif ($entity instanceof Photo) {
            $description = sprintf(
                '%s %s updated photo "%s" (ID %d).',
                $this->security->isGranted('ROLE_ADMIN') ? 'Admin' : 'Photographer',
                $actor->getUsername() ?: ($actor->getEmail() ?? 'unknown'),
                $entity->getTitle() ?? 'Untitled',
                $entity->getId() ?? 0,
            );

            $this->activityLogger->log('UPDATE', $actor, 'PHOTO', $entity->getId(), $description);
        }
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        $actor = $this->security->getUser();

        if (!$actor instanceof User) {
            return;
        }

        if ($entity instanceof User) {
            // Admin deletes a user
            if ($this->security->isGranted('ROLE_ADMIN')) {
                $description = sprintf(
                    'Admin %s deleted user %s (ID %d).',
                    $actor->getUsername() ?: ($actor->getEmail() ?? 'unknown'),
                    $entity->getUsername() ?: ($entity->getEmail() ?? 'unknown'),
                    $entity->getId() ?? 0,
                );

                $this->activityLogger->log('DELETE', $actor, 'USER', $entity->getId(), $description);
            }
        } elseif ($entity instanceof Album) {
            $description = sprintf(
                '%s %s deleted album "%s" (ID %d).',
                $this->security->isGranted('ROLE_ADMIN') ? 'Admin' : 'Photographer',
                $actor->getUsername() ?: ($actor->getEmail() ?? 'unknown'),
                $entity->getTitle() ?? 'Untitled',
                $entity->getId() ?? 0,
            );

            $this->activityLogger->log('DELETE', $actor, 'ALBUM', $entity->getId(), $description);
        } elseif ($entity instanceof Photo) {
            $description = sprintf(
                '%s %s deleted photo "%s" (ID %d).',
                $this->security->isGranted('ROLE_ADMIN') ? 'Admin' : 'Photographer',
                $actor->getUsername() ?: ($actor->getEmail() ?? 'unknown'),
                $entity->getTitle() ?? 'Untitled',
                $entity->getId() ?? 0,
            );

            $this->activityLogger->log('DELETE', $actor, 'PHOTO', $entity->getId(), $description);
        }
    }
}
