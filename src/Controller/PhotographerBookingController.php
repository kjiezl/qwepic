<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\BookingAttachment;
use App\Entity\User;
use App\Form\BookingCompleteType;
use App\Form\BookingRejectType;
use App\Repository\AlbumRepository;
use App\Repository\BookingAttachmentRepository;
use App\Repository\BookingRepository;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PhotographerBookingController extends AbstractController
{
    #[Route('/dashboard/photographer/bookings', name: 'app_photographer_booking_index', methods: ['GET'])]
    #[IsGranted('ROLE_PHOTOGRAPHER')]
    public function index(BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $requested = $bookingRepository->findBy(
            ['photographer' => $user, 'status' => 'requested'],
            ['startAt' => 'ASC']
        );

        $accepted = $bookingRepository->findBy(
            ['photographer' => $user, 'status' => 'accepted'],
            ['startAt' => 'ASC']
        );

        $completed = $bookingRepository->findBy(
            ['photographer' => $user, 'status' => 'completed'],
            ['startAt' => 'DESC']
        );

        return $this->render('photographer_booking/index.html.twig', [
            'requestedBookings' => $requested,
            'acceptedBookings' => $accepted,
            'completedBookings' => $completed,
        ]);
    }

    #[Route('/dashboard/photographer/bookings/{id}', name: 'app_photographer_booking_show', methods: ['GET'])]
    #[IsGranted('ROLE_PHOTOGRAPHER')]
    public function show(Booking $booking, BookingAttachmentRepository $bookingAttachmentRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($booking->getPhotographer() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $attachments = $bookingAttachmentRepository->findBy(['booking' => $booking], ['id' => 'ASC']);

        return $this->render('photographer_booking/show.html.twig', [
            'booking' => $booking,
            'attachments' => $attachments,
        ]);
    }

    #[Route('/dashboard/photographer/bookings/{id}/accept', name: 'app_photographer_booking_accept', methods: ['POST'])]
    #[IsGranted('ROLE_PHOTOGRAPHER')]
    public function accept(Request $request, Booking $booking, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($booking->getPhotographer() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($booking->getStatus() !== 'requested') {
            return $this->redirectToRoute('app_photographer_booking_index');
        }

        if ($this->isCsrfTokenValid('accept_booking_'.$booking->getId(), $request->getPayload()->getString('_token'))) {
            $booking->setStatus('accepted');
            $booking->setRejectionReason(null);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_photographer_booking_index');
    }

    #[Route('/dashboard/photographer/bookings/{id}/reject', name: 'app_photographer_booking_reject', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PHOTOGRAPHER')]
    public function reject(Request $request, Booking $booking, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($booking->getPhotographer() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($booking->getStatus() !== 'requested') {
            return $this->redirectToRoute('app_photographer_booking_index');
        }

        $form = $this->createForm(BookingRejectType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($booking->getRejectionReason() === null || trim((string) $booking->getRejectionReason()) === '') {
                $this->addFlash('error', 'Please provide a reason for rejection.');
            } else {
                $booking->setStatus('rejected');
                $entityManager->flush();
                return $this->redirectToRoute('app_photographer_booking_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('photographer_booking/reject.html.twig', [
            'booking' => $booking,
            'form' => $form,
        ]);
    }

    #[Route('/dashboard/photographer/bookings/{id}/complete', name: 'app_photographer_booking_complete', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PHOTOGRAPHER')]
    public function complete(
        Request $request,
        Booking $booking,
        AlbumRepository $albumRepository,
        PhotoRepository $photoRepository,
        BookingAttachmentRepository $bookingAttachmentRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($booking->getPhotographer() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($booking->getStatus() !== 'accepted') {
            return $this->redirectToRoute('app_photographer_booking_index');
        }

        $albums = $albumRepository->findBy(['photographer' => $user], ['created_at' => 'DESC']);
        $photos = $photoRepository->findByPhotographer($user);

        $form = $this->createForm(BookingCompleteType::class, null, [
            'albums' => $albums,
            'photos' => $photos,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedAlbum = $form->get('album')->getData();
            $selectedPhotos = $form->get('photos')->getData();

            $hasAlbum = $selectedAlbum !== null;
            $selectedPhotosArray = [];
            if ($selectedPhotos !== null) {
                $selectedPhotosArray = is_array($selectedPhotos) ? $selectedPhotos : iterator_to_array($selectedPhotos);
            }
            $hasPhotos = count($selectedPhotosArray) > 0;

            foreach ($bookingAttachmentRepository->findBy(['booking' => $booking]) as $existing) {
                $entityManager->remove($existing);
            }

            if ($selectedAlbum !== null) {
                $attachment = new BookingAttachment();
                $attachment->setBooking($booking);
                $attachment->setAlbum($selectedAlbum);
                $entityManager->persist($attachment);
            }

            foreach ($selectedPhotosArray as $photo) {
                    $attachment = new BookingAttachment();
                    $attachment->setBooking($booking);
                    $attachment->setPhoto($photo);
                    $entityManager->persist($attachment);
            }

            $booking->setStatus('completed');
            $entityManager->flush();

            return $this->redirectToRoute('app_photographer_booking_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('photographer_booking/complete.html.twig', [
            'booking' => $booking,
            'form' => $form,
        ]);
    }
}
