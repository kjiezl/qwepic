<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class VerificationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $router,
        private MailerInterface $mailer,
    ) {}

    public function generateToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        $user->setVerificationToken($token);
        $user->setIsVerified(false);
        $this->em->flush();
        return $token;
    }

    public function sendVerificationEmail(User $user, string $token): void
    {
        $verifyUrl = $this->router->generate(
            'app_verify_email',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from('kjiezl08@gmail.com')
            ->to($user->getEmail())
            ->subject('Verify your QwePic account')
            ->html("
                <div style='font-family: Inter, sans-serif; max-width: 500px; margin: 0 auto;'>
                    <h2 style='color: #1d1e35;'>Welcome to QwePic, {$user->getUsername()}!</h2>
                    <p>Thanks for registering. Please verify your email address to activate your account.</p>
                    <a href='{$verifyUrl}'
                       style='display: inline-block; background: #42deff; color: #1d1e35;
                              padding: 12px 24px; border-radius: 8px; text-decoration: none;
                              font-weight: 600; margin: 16px 0;'>
                        Verify My Email
                    </a>
                    <p style='color: #6c757d; font-size: 0.875rem;'>
                        If you didn't create an account, you can safely ignore this email.
                    </p>
                    <p style='color: #6c757d; font-size: 0.875rem;'>
                        Or copy this link: {$verifyUrl}
                    </p>
                </div>
            ");

        $this->mailer->send($email);
    }

    public function verifyByToken(string $token): ?User
    {
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            return null;
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $this->em->flush();

        return $user;
    }
}