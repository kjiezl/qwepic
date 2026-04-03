<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Your account has been disabled. Please contact an administrator.');
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException('Your email address is not verified. Please check your inbox for the verification link.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No additional checks after authentication for now.
    }
}
