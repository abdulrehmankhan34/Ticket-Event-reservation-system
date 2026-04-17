<?php

namespace App\Controller;

use App\Entity\TicketTier;
use App\Entity\User;
use App\Repository\SeatReservationRepository;
use App\Service\CheckoutService;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart_show', methods: ['GET'])]
    public function show(SeatReservationRepository $reservations): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $rows = $reservations->findActiveForUser($user);

        return $this->render('cart/show.html.twig', [
            'reservations' => $rows,
        ]);
    }

    #[Route('/cart/add/{tierId}', name: 'app_cart_add', methods: ['POST'])]
    public function add(int $tierId, Request $request, ReservationService $reservationService, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $qty = (int) $request->request->get('quantity', 1);
        $tier = $em->getRepository(TicketTier::class)->find($tierId);
        if (!$tier instanceof TicketTier) {
            throw $this->createNotFoundException('Tier not found.');
        }

        try {
            $reservationService->reserve($user, $tier, $qty);
            $this->addFlash('success', 'Reserved tickets in your cart (10 minutes hold).');
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/checkout', name: 'app_checkout', methods: ['POST'])]
    public function checkout(Request $request, CheckoutService $checkoutService, RateLimiterFactory $checkoutLimiter): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $limit = $checkoutLimiter->create((string) $user->getId())->consume();
        if (!$limit->isAccepted()) {
            $this->addFlash('error', 'Too many checkout attempts. Please wait and try again.');
            return $this->redirectToRoute('app_cart_show');
        }

        $idempotencyKey = (string) ($request->headers->get('X-Idempotency-Key') ?: $request->request->get('idempotency_key', ''));
        if ($idempotencyKey === '') {
            $idempotencyKey = bin2hex(random_bytes(16));
        }

        try {
            $booking = $checkoutService->checkoutUserPendingReservations($user, $idempotencyKey);
            $this->addFlash('success', 'Checkout confirmed. Booking ID: '.$booking->getId());
            return $this->redirectToRoute('app_booking_show', ['id' => $booking->getId()]);
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_cart_show');
        }
    }
}

