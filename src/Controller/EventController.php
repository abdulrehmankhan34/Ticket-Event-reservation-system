<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\TicketTierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EventController extends AbstractController
{
    #[Route('/events', name: 'app_events_index', methods: ['GET'])]
    public function index(Request $request, EventRepository $events, TicketTierRepository $tiers): Response
    {
        $filters = [
            'q' => $request->query->get('q', ''),
            'category' => $request->query->get('category', ''),
            'date_from' => $request->query->get('date_from', ''),
            'date_to' => $request->query->get('date_to', ''),
            'online' => $request->query->get('online', ''),
        ];

        $rows = $events->findUpcomingWithFilters($filters);

        $eventIds = array_values(array_filter(array_map(static fn (Event $e) => $e->getId(), $rows)));
        $tiersByEvent = [];
        $remainingByTierId = [];

        if ($eventIds !== []) {
            $tierRows = $tiers->findByEventIds($eventIds);

            $tierIds = [];
            foreach ($tierRows as $t) {
                $tierIds[] = (int) $t->getId();
                $eventId = (int) $t->getEvent()->getId();
                $tiersByEvent[$eventId][] = $t;
            }

            $confirmed = $tiers->getConfirmedQuantityByTierIds($tierIds);
            $reserved = $tiers->getActiveReservedQuantityByTierIds($tierIds);

            foreach ($tierRows as $t) {
                $id = (int) $t->getId();
                $sold = (int) ($confirmed[$id] ?? 0);
                $held = (int) ($reserved[$id] ?? 0);
                $remainingByTierId[$id] = max(0, $t->getTotalSeats() - $sold - $held);
            }
        }

        return $this->render('events/index.html.twig', [
            'filters' => $filters,
            'events' => $rows,
            'tiersByEvent' => $tiersByEvent,
            'remainingByTierId' => $remainingByTierId,
        ]);
    }

    #[Route('/events/{id}', name: 'app_events_show', methods: ['GET'])]
    public function show(Event $event, TicketTierRepository $tiers): Response
    {
        $tierRows = $tiers->findBy(['event' => $event], ['basePrice' => 'ASC']);
        $tierIds = array_values(array_filter(array_map(static fn ($t) => (int) $t->getId(), $tierRows)));

        $remainingByTierId = [];
        if ($tierIds !== []) {
            $confirmed = $tiers->getConfirmedQuantityByTierIds($tierIds);
            $reserved = $tiers->getActiveReservedQuantityByTierIds($tierIds);

            foreach ($tierRows as $t) {
                $id = (int) $t->getId();
                $sold = (int) ($confirmed[$id] ?? 0);
                $held = (int) ($reserved[$id] ?? 0);
                $remainingByTierId[$id] = max(0, $t->getTotalSeats() - $sold - $held);
            }
        }

        return $this->render('events/show.html.twig', [
            'event' => $event,
            'tiers' => $tierRows,
            'remainingByTierId' => $remainingByTierId,
        ]);
    }
}

