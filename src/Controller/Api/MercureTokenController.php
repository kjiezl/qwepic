<?php

namespace App\Controller\Api;

use App\Entity\User;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MercureTokenController extends AbstractController
{
    #[Route('/api/mercure/token', name: 'api_mercure_token', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $secret = $this->getParameter('mercure_jwt_secret');
        $baseUrl = $this->getParameter('api_base_url');

        $payload = [
            'mercure' => [
                'subscribe' => [
                    sprintf('%s/api/users/%d/bookings', $baseUrl, $user->getId()),
                    sprintf('%s/api/photographers', $baseUrl),
                    sprintf('%s/api/users/%d/profile', $baseUrl, $user->getId()),
                ],
            ],
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        return $this->json(['token' => $token]);
    }
}
