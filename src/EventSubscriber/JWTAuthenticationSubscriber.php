<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JWTAuthenticationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_CREATED => 'onJWTCreated',
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $payload = $event->getData();
        $payload['user_id'] = $user->getId();
        $payload['email'] = $user->getEmail();
        $payload['roles'] = $user->getRoles();
        $event->setData($payload);
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActive()) {
            $event->setData([
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Your account has been disabled. Please contact an administrator.',
            ]);
            $event->getResponse()->setStatusCode(Response::HTTP_FORBIDDEN);
            return;
        }

        if (!$user->isVerified()) {
            $event->setData([
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Your email address is not verified. Please check your inbox for the verification link.',
            ]);
            $event->getResponse()->setStatusCode(Response::HTTP_FORBIDDEN);
            return;
        }

        $data = $event->getData();
        $data['user'] = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'is_verified' => $user->isVerified(),
        ];
        $event->setData($data);
    }
}
