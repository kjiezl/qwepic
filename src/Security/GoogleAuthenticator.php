<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $em,
        private RouterInterface $router,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();
                $googleId = $googleUser->getId();

                // Check if user already exists by googleId
                $existingUser = $this->em->getRepository(User::class)
                    ->findOneBy(['googleId' => $googleId]);

                if ($existingUser) {
                    return $existingUser;
                }

                // Check if user exists by email
                $existingUser = $this->em->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                if ($existingUser) {
                    // Link Google account to existing user
                    $existingUser->setGoogleId($googleId);
                    // Auto-verify on Google login
                    if (method_exists($existingUser, 'setIsVerified')) {
                        $existingUser->setIsVerified(true);
                    }
                    $this->em->flush();
                    return $existingUser;
                }

                // Create new user from Google account
                $user = new User();
                $user->setGoogleId($googleId);
                $user->setEmail($email);
                $user->setUsername($googleUser->getName() ?? $email);
                $user->setPassword(''); // No password for OAuth users
                $user->setRoles(['ROLE_PHOTOGRAPHER']); // Google login = Photographer
                $user->setIsActive(true);
                // Auto-verified since Google confirmed their email
                if (method_exists($user, 'setIsVerified')) {
                    $user->setIsVerified(true);
                }

                $this->em->persist($user);
                $this->em->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->router->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set('google_auth_error', $exception->getMessage());
        return new RedirectResponse($this->router->generate('app_login'));
    }
}