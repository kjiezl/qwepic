<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\ActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class AuthenticationActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ActivityLogger $activityLogger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InteractiveLoginEvent::class => 'onLogin',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        $description = sprintf(
            'User %s logged in.',
            $user->getUsername() ?: ($user->getEmail() ?? 'unknown'),
        );

        $this->activityLogger->log('LOGIN', $user, 'USER', $user->getId(), $description);
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();

        if (!$user instanceof User) {
            return;
        }

        $description = sprintf(
            'User %s logged out.',
            $user->getUsername() ?: ($user->getEmail() ?? 'unknown'),
        );

        $this->activityLogger->log('LOGOUT', $user, 'USER', $user->getId(), $description);
    }
}
