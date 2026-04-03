<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\VerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        VerificationService $verificationService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'message' => 'Invalid JSON data',
            ], Response::HTTP_BAD_REQUEST);
        }

        $username = $data['username'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$username || !$email || !$password) {
            return $this->json([
                'message' => 'Missing required fields: username, email, password',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if username already exists
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUser) {
            return $this->json([
                'message' => 'Username already exists',
            ], Response::HTTP_CONFLICT);
        }

        // Check if email already exists
        $existingEmail = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingEmail) {
            return $this->json([
                'message' => 'Email already exists',
            ], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);

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

        $entityManager->persist($user);
        $entityManager->flush();

        $token = $verificationService->generateToken($user);

        return $this->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->getId(),
                'verification_token' => $token,
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/auth/verify/{token}', name: 'api_verify_email', methods: ['GET'])]
    public function verifyEmail(string $token, VerificationService $verificationService): JsonResponse
    {
        $user = $verificationService->verifyByToken($token);

        if (!$user) {
            return $this->json([
                'message' => 'Invalid or expired verification token.',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'message' => 'Email verified successfully.',
            'user' => [
                'id'          => $user->getId(),
                'username'    => $user->getUsername(),
                'is_verified' => $user->isVerified(),
            ],
        ]);
    }
}
