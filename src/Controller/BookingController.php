<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\User;
use App\Repository\BookingItemRepository;
use App\Repository\ETicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class BookingController extends AbstractController
{
    #[Route('/bookings/{id}', name: 'app_booking_show', methods: ['GET'])]
    public function show(Booking $booking, BookingItemRepository $items, ETicketRepository $tickets): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($booking->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $bookingItems = $items->findByBooking($booking);
        $ticketByItemId = [];
        foreach ($bookingItems as $bi) {
            $t = $tickets->findOneByBookingItem($bi);
            if ($t) {
                $ticketByItemId[(int) $bi->getId()] = $t;
            }
        }

        return $this->render('bookings/show.html.twig', [
            'booking' => $booking,
            'items' => $bookingItems,
            'ticketByItemId' => $ticketByItemId,
        ]);
    }
}

