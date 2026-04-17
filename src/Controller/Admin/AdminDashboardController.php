<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\BookingItemRepository;
use App\Service\EventCancellationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminDashboardController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function index(EntityManagerInterface $em, BookingItemRepository $bookingItems): Response
    {
        $users = (int) $em->getRepository(User::class)->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $events = (int) $em->getRepository(Event::class)->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $ticketsSold = $bookingItems->countTotalTicketsSold();
        $systemRevenue = $bookingItems->sumSystemFees();

        $recentEvents = $em->getRepository(Event::class)->findBy([], ['createdAt' => 'DESC'], 20);

        return $this->render('admin/dashboard.html.twig', [
            'users' => $users,
            'events' => $events,
            'ticketsSold' => $ticketsSold,
            'systemRevenue' => $systemRevenue,
            'recentEvents' => $recentEvents,
        ]);
    }

    #[Route('/events/{id}/cancel', name: 'app_admin_event_cancel', methods: ['POST'])]
    public function cancelEvent(
        Event $event,
        Request $request,
        EventCancellationService $canceller,
    ): Response {
        if (!$this->isCsrfTokenValid('cancel_event_'.$event->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $refunded = $canceller->cancelAndRefund($event);
        $this->addFlash('success', sprintf('Event cancelled. Refunded %d bookings.', $refunded));

        return $this->redirectToRoute('app_admin_dashboard');
    }
}

