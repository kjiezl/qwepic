<?php

namespace App\EventListener;

use App\Entity\Booking;
use App\Service\MercurePublisher;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class BookingEntityListener
{
    public function __construct(private MercurePublisher $publisher) {}

    public function postPersist(Booking $booking, LifecycleEventArgs $event): void
    {
    }

    public function postUpdate(Booking $booking, LifecycleEventArgs $event): void
    {
    }
}
