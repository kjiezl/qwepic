<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Repository\UserRepository;

class LoginController extends AbstractController
{
    /**
     * Get current authenticated user info.
     * Requires valid JWT token in Authorization header.
     */
    #[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json([
                'message' => 'Not authenticated',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUserIdentifier(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'is_active' => $user->isActive(),
        ]);
    }

    /**
     * Get current authenticated user profile.
     * Requires valid JWT token in Authorization header.
     */
    #[Route('/api/auth/profile', name: 'api_auth_profile', methods: ['GET'])]
    public function profile(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json([
                'message' => 'Not authenticated',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'is_active' => $user->isActive(),
            'created_at' => $user->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }

    /**
     * Refresh JWT token.
     * Send current valid token to get a new one with extended expiration.
     */
    #[Route('/api/auth/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json([
                'message' => 'Invalid or expired token',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // The actual token refresh is handled by lexik_jwt_authentication
        // This endpoint just validates the user is still valid
        return $this->json([
            'message' => 'Token is valid. Use /api/token/refresh for actual refresh.',
            'user' => $user->getUserIdentifier(),
        ]);
    }

    /**
     * Google Sign-In authentication endpoint for mobile app.
     * Receives Google ID token from React Native app and creates/logs in user.
     */
    #[Route('/api/auth/google', name: 'api_auth_google', methods: ['POST'])]
    public function googleAuth(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        try {
            // Parse request data
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['idToken'])) {
                return $this->json([
                    'message' => 'Missing Google ID token',
                ], Response::HTTP_BAD_REQUEST);
            }

            $idToken = $data['idToken'];
            $email = $data['email'] ?? null;
            $displayName = $data['displayName'] ?? null;

            if (!$email) {
                return $this->json([
                    'message' => 'Missing email from Google Sign-In',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Try to find user by email first
            $user = $userRepository->findOneBy(['email' => $email]);

            // If user doesn't exist, create new user
            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                
                // Generate username from displayName or email
                if ($displayName) {
                    $username = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($displayName));
                    $username = substr($username, 0, 50);
                } else {
                    $username = explode('@', $email)[0];
                    $username = substr($username, 0, 50);
                }

                // Ensure username is unique
                $counter = 1;
                $baseUsername = $username;
                while ($userRepository->findOneBy(['username' => $username])) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }

                $user->setUsername($username);
                
                // Set a random password for OAuth users
                $randomPassword = bin2hex(random_bytes(16));
                $hashedPassword = $passwordHasher->hashPassword($user, $randomPassword);
                $user->setPassword($hashedPassword);
                
                $user->setIsActive(true);
                $user->setIsVerified(true); // Trust Google's verification
                
                $entityManager->persist($user);
            }

            // Store Google ID
            if (!$user->getGoogleId()) {
                $googleId = hash('sha256', $idToken);
                $user->setGoogleId($googleId);
            }

            $entityManager->flush();

            // Generate JWT token
            $token = $jwtManager->create($user);

            return $this->json([
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'username' => $user->getUsername(),
                    'is_active' => $user->isActive(),
                ],
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            error_log('Google Auth Error: ' . $e->getMessage());
            
            return $this->json([
                'message' => 'Authentication failed',
                'error' => $e->getMessage(),
            ], Response::HTTP_UNAUTHORIZED);
        }
    }
}
