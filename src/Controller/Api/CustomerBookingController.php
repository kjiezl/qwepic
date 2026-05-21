<?php

namespace App\Controller\Api;

use App\Entity\Booking;
use App\Entity\User;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/customer/bookings')]
class CustomerBookingController extends AbstractController
{
    #[Route('', name: 'api_customer_bookings_list', methods: ['GET'])]
    public function list(#[CurrentUser] ?User $user, BookingRepository $bookingRepository): JsonResponse
    {
        if (null === $user) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $bookings = $bookingRepository->findBy(['client' => $user], ['createdAt' => 'DESC']);

        $data = array_map(fn(Booking $b) => [
            'id' => $b->getId(),
            'photographer' => [
                'id' => $b->getPhotographer()?->getId(),
                'username' => $b->getPhotographer()?->getUsername(),
            ],
            'status' => $b->getStatus(),
            'start_at' => $b->getStartAt()?->format(\DateTimeInterface::ATOM),
            'end_at' => $b->getEndAt()?->format(\DateTimeInterface::ATOM),
            'location' => $b->getLocation(),
            'notes' => $b->getNotes(),
            'rejection_reason' => $b->getRejectionReason(),
            'created_at' => $b->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $b->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ], $bookings);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_customer_bookings_show', methods: ['GET'])]
    public function show(#[CurrentUser] ?User $user, Booking $booking): JsonResponse
    {
        if (null === $user) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        if ($booking->getClient() !== $user) {
            return $this->json(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'id' => $booking->getId(),
            'photographer' => [
                'id' => $booking->getPhotographer()?->getId(),
                'username' => $booking->getPhotographer()?->getUsername(),
                'email' => $booking->getPhotographer()?->getEmail(),
            ],
            'status' => $booking->getStatus(),
            'start_at' => $booking->getStartAt()?->format(\DateTimeInterface::ATOM),
            'end_at' => $booking->getEndAt()?->format(\DateTimeInterface::ATOM),
            'location' => $booking->getLocation(),
            'notes' => $booking->getNotes(),
            'rejection_reason' => $booking->getRejectionReason(),
            'created_at' => $booking->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $booking->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('', name: 'api_customer_bookings_create', methods: ['POST'])]
    public function create(
        #[CurrentUser] ?User $user,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        if (null === $user) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['message' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        $photographerId = $data['photographer_id'] ?? null;
        $startAt = $data['start_at'] ?? null;
        $endAt = $data['end_at'] ?? null;
        $location = $data['location'] ?? null;
        $notes = $data['notes'] ?? null;

        if (!$photographerId || !$startAt || !$endAt) {
            return $this->json([
                'message' => 'Missing required fields: photographer_id, start_at, end_at',
            ], Response::HTTP_BAD_REQUEST);
        }

        $photographer = $em->getRepository(User::class)->find($photographerId);

        if (!$photographer || !in_array('ROLE_PHOTOGRAPHER', $photographer->getRoles(), true)) {
            return $this->json(['message' => 'Photographer not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$photographer->isActive()) {
            return $this->json(['message' => 'Photographer is not available'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $startAtDate = new \DateTimeImmutable($startAt);
            $endAtDate = new \DateTimeImmutable($endAt);
        } catch (\Exception) {
            return $this->json(['message' => 'Invalid date format. Use ISO 8601.'], Response::HTTP_BAD_REQUEST);
        }

        $booking = new Booking();
        $booking->setClient($user);
        $booking->setPhotographer($photographer);
        $booking->setStatus('requested');
        $booking->setStartAt($startAtDate);
        $booking->setEndAt($endAtDate);
        $booking->setLocation($location);
        $booking->setNotes($notes);

        $errors = $validator->validate($booking);
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

        $em->persist($booking);
        $em->flush();

        return $this->json([
            'message' => 'Booking created successfully',
            'booking' => [
                'id' => $booking->getId(),
                'photographer' => [
                    'id' => $photographer->getId(),
                    'username' => $photographer->getUsername(),
                ],
                'status' => $booking->getStatus(),
                'start_at' => $booking->getStartAt()?->format(\DateTimeInterface::ATOM),
                'end_at' => $booking->getEndAt()?->format(\DateTimeInterface::ATOM),
                'location' => $booking->getLocation(),
                'notes' => $booking->getNotes(),
                'created_at' => $booking->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}/cancel', name: 'api_customer_bookings_cancel', methods: ['PATCH'])]
    public function cancel(
        #[CurrentUser] ?User $user,
        Booking $booking,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (null === $user) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        if ($booking->getClient() !== $user) {
            return $this->json(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        if ($booking->getStatus() !== 'requested') {
            return $this->json([
                'message' => 'Only bookings with status "requested" can be cancelled',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $booking->setStatus('cancelled');
        $em->flush();

        return $this->json([
            'message' => 'Booking cancelled successfully',
            'booking' => [
                'id' => $booking->getId(),
                'status' => $booking->getStatus(),
                'updated_at' => $booking->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            ],
        ]);
    }
}
