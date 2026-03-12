<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;

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
}
