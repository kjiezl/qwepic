<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingAttachmentRepository;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AdminBookingController extends AbstractController
{
    #[Route('/dashboard/bookings', name: 'app_admin_booking_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(BookingRepository $bookingRepository): Response
    {
        $bookings = $bookingRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin_booking/index.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/dashboard/bookings/{id}', name: 'app_admin_booking_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Booking $booking, BookingAttachmentRepository $bookingAttachmentRepository): Response
    {
        $attachments = $bookingAttachmentRepository->findBy(['booking' => $booking], ['id' => 'ASC']);

        return $this->render('admin_booking/show.html.twig', [
            'booking' => $booking,
            'attachments' => $attachments,
        ]);
    }
}
