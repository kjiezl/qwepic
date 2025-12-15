<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\User;
use App\Form\BookingRequestType;
use App\Repository\BookingAttachmentRepository;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class BookingController extends AbstractController
{
    #[Route('/bookings', name: 'app_booking_index', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isGranted('ROLE_PHOTOGRAPHER') || $this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $bookings = $bookingRepository->findBy(['client' => $user], ['createdAt' => 'DESC']);

        return $this->render('booking/index.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/photographers/{id}/book', name: 'app_booking_new', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new(
        Request $request,
        User $photographer,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isGranted('ROLE_PHOTOGRAPHER') || $this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if (!in_array('ROLE_PHOTOGRAPHER', $photographer->getRoles(), true)) {
            throw $this->createNotFoundException();
        }

        $booking = new Booking();
        $booking->setClient($user);
        $booking->setPhotographer($photographer);
        $booking->setStatus('requested');

        $form = $this->createForm(BookingRequestType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($booking);
            $entityManager->flush();

            return $this->redirectToRoute('app_booking_show', ['id' => $booking->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('booking/new.html.twig', [
            'photographer' => $photographer,
            'booking' => $booking,
            'form' => $form,
        ]);
    }

    #[Route('/bookings/{id}', name: 'app_booking_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(Booking $booking, BookingAttachmentRepository $bookingAttachmentRepository): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isGranted('ROLE_PHOTOGRAPHER')) {
            throw $this->createAccessDeniedException();
        }

        $isParticipant = ($booking->getClient() === $user);
        if (!$isParticipant && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $attachments = $bookingAttachmentRepository->findBy(['booking' => $booking], ['id' => 'ASC']);

        return $this->render('booking/show.html.twig', [
            'booking' => $booking,
            'attachments' => $attachments,
        ]);
    }
}
