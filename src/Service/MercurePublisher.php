<?php

namespace App\Service;

use App\Entity\Booking;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

class MercurePublisher
{
    public function __construct(
        private HubInterface $hub,
        private SerializerInterface $serializer,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(API_BASE_URL)%')]
        private string $baseUrl,
    ) {}

    // ── Bookings ────────────────────────────────────────────────

    public function publishBookingCreated(Booking $booking): void
    {
        $this->publishBookingEvent($booking, 'booking.created');
    }

    public function publishBookingUpdated(Booking $booking): void
    {
        $this->publishBookingEvent($booking, 'booking.updated');
    }

    public function publishBookingCancelled(Booking $booking): void
    {
        $this->publishBookingEvent($booking, 'booking.cancelled');
    }

    private function publishBookingEvent(Booking $booking, string $event): void
    {
        $topic = sprintf('%s/api/users/%d/bookings',
            $this->baseUrl,
            $booking->getClient()->getId()
        );

        $data = json_encode([
            'event' => $event,
            'data'  => json_decode($this->serializer->serialize($booking, 'json', [
                'groups' => ['booking:read'],
            ]), true),
        ]);

        $this->hub->publish(new Update($topic, $data));
    }

    // ── Photographers ───────────────────────────────────────────

    public function publishPhotographerCreated($photographer): void
    {
        $this->publishPhotographerEvent($photographer, 'photographer.created');
    }

    public function publishPhotographerUpdated($photographer): void
    {
        $this->publishPhotographerEvent($photographer, 'photographer.updated');
    }

    public function publishPhotographerDeleted($photographer): void
    {
        $this->publishPhotographerEvent($photographer, 'photographer.deleted');
    }

    private function publishPhotographerEvent($photographer, string $event): void
    {
        $topic = sprintf('%s/api/photographers', $this->baseUrl);

        $data = json_encode([
            'event' => $event,
            'data'  => json_decode($this->serializer->serialize($photographer, 'json', [
                'groups' => ['photographer:read'],
            ]), true),
        ]);

        $this->hub->publish(new Update($topic, $data));
    }

    // ── Profile ─────────────────────────────────────────────────

    public function publishProfileUpdated($user): void
    {
        $topic = sprintf('%s/api/users/%d/profile', $this->baseUrl, $user->getId());

        $data = json_encode([
            'event' => 'profile.updated',
            'data'  => json_decode($this->serializer->serialize($user, 'json', [
                'groups' => ['profile:read'],
            ]), true),
        ]);

        $this->hub->publish(new Update($topic, $data));
    }
}
