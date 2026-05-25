<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/customer/profile')]
class CustomerProfileController extends AbstractController
{
    #[Route('', name: 'api_customer_profile_show', methods: ['GET'])]
    public function show(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'is_active' => $user->isActive(),
            'is_verified' => $user->isVerified(),
            'created_at' => $user->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $user->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('', name: 'api_customer_profile_update', methods: ['PATCH'])]
    public function update(
        #[CurrentUser] ?User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        MercurePublisher $mercurePublisher,
    ): JsonResponse {
        if (null === $user) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['message' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['username'])) {
            $existing = $em->getRepository(User::class)->findOneBy(['username' => $data['username']]);
            if ($existing && $existing->getId() !== $user->getId()) {
                return $this->json(['message' => 'Username already taken'], Response::HTTP_CONFLICT);
            }
            $user->setUsername($data['username']);
        }

        if (isset($data['email'])) {
            $existing = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existing && $existing->getId() !== $user->getId()) {
                return $this->json(['message' => 'Email already taken'], Response::HTTP_CONFLICT);
            }
            $user->setEmail($data['email']);
        }

        if (isset($data['password'])) {
            if (strlen($data['password']) < 6) {
                return $this->json([
                    'message' => 'Password must be at least 6 characters',
                ], Response::HTTP_BAD_REQUEST);
            }
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'message' => 'Validation failed',
                'errors' => $errorMessages,
            ], Response::HTTP_BAD_REQUEST);
        }

        $em->flush();
        try {
            $mercurePublisher->publishProfileUpdated($user);
        } catch (\Throwable) {
        }

        return $this->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'is_active' => $user->isActive(),
                'is_verified' => $user->isVerified(),
                'updated_at' => $user->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            ],
        ]);
    }
}
