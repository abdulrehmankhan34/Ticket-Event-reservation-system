<?php

namespace App\Controller\Organizer;

use App\Entity\Event;
use App\Entity\TicketTier;
use App\Entity\User;
use App\Form\TicketTierType;
use App\Repository\TicketTierRepository;
use App\Security\OrganizerAccess;
use App\Security\Voter\EventVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ORGANIZER')]
#[Route('/organizer/events/{eventId}/tiers')]
class OrganizerTicketTierController extends AbstractController
{
    private function getOwnedEvent(int $eventId, OrganizerAccess $access, EntityManagerInterface $em): Event
    {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $access->requireApprovedOrganizer($user);

        $event = $em->getRepository(Event::class)->find($eventId);
        if (!$event instanceof Event) {
            throw $this->createNotFoundException('Event not found.');
        }

        $this->denyAccessUnlessGranted(EventVoter::MANAGE, $event);

        return $event;
    }

    #[Route('', name: 'app_organizer_tiers_index', methods: ['GET'])]
    public function index(int $eventId, OrganizerAccess $access, EntityManagerInterface $em, TicketTierRepository $tiers): Response
    {
        $event = $this->getOwnedEvent($eventId, $access, $em);
        $tierRows = $em->getRepository(TicketTier::class)->findBy(['event' => $event], ['basePrice' => 'ASC']);

        $remainingByTierId = [];
        $tierIds = array_values(array_filter(array_map(static fn (TicketTier $t) => (int) $t->getId(), $tierRows)));
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

        return $this->render('organizer/tiers/index.html.twig', [
            'event' => $event,
            'tiers' => $tierRows,
            'remainingByTierId' => $remainingByTierId,
        ]);
    }

    #[Route('/new', name: 'app_organizer_tiers_new', methods: ['GET', 'POST'])]
    public function new(int $eventId, Request $request, OrganizerAccess $access, EntityManagerInterface $em): Response
    {
        $event = $this->getOwnedEvent($eventId, $access, $em);

        $tier = new TicketTier($event);
        $form = $this->createForm(TicketTierType::class, $tier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($tier);
            $em->flush();

            return $this->redirectToRoute('app_organizer_tiers_index', ['eventId' => $eventId]);
        }

        return $this->render('organizer/tiers/form.html.twig', [
            'title' => 'Create tier',
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_organizer_tiers_edit', methods: ['GET', 'POST'])]
    public function edit(int $eventId, TicketTier $tier, Request $request, OrganizerAccess $access, EntityManagerInterface $em): Response
    {
        $event = $this->getOwnedEvent($eventId, $access, $em);
        if ($tier->getEvent()->getId() !== $event->getId()) {
            throw $this->createNotFoundException('Tier not found for this event.');
        }

        $form = $this->createForm(TicketTierType::class, $tier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_organizer_tiers_index', ['eventId' => $eventId]);
        }

        return $this->render('organizer/tiers/form.html.twig', [
            'title' => 'Edit tier',
            'event' => $event,
            'form' => $form,
        ]);
    }
}

