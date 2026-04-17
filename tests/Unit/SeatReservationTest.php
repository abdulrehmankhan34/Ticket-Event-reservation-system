<?php

namespace App\Tests\Unit;

use App\Entity\SeatReservation;
use App\Entity\TicketTier;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class SeatReservationTest extends TestCase
{
    public function testIsActiveDependsOnStatusAndExpiry(): void
    {
        $user = new User();
        $user->setEmail('u@example.com')->setPassword('x');

        $event = $this->createStub(\App\Entity\Event::class);
        $tier = new TicketTier($event);

        $now = new \DateTimeImmutable('2026-01-01 10:00:00');
        $active = new SeatReservation($user, $tier, 1, $now, $now->modify('+10 minutes'));
        self::assertTrue($active->isActive($now));

        $expired = new SeatReservation($user, $tier, 1, $now, $now->modify('-1 minute'));
        self::assertFalse($expired->isActive($now));

        $confirmed = new SeatReservation($user, $tier, 1, $now, $now->modify('+10 minutes'));
        $confirmed->setStatus(SeatReservation::STATUS_CONFIRMED);
        self::assertFalse($confirmed->isActive($now));
    }
}

