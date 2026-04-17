<?php

namespace App\Controller\Organizer;

use App\Entity\Event;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\BookingItemRepository;
use App\Repository\TicketTierRepository;
use App\Security\OrganizerAccess;
use App\Security\Voter\EventVoter;
use App\Service\EventCancellationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_ORGANIZER')]
#[Route('/organizer/events')]
class OrganizerEventController extends AbstractController
{
    #[Route('', name: 'app_organizer_events_index', methods: ['GET'])]
    public function index(OrganizerAccess $access, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $access->requireApprovedOrganizer($user);

        $events = $em->getRepository(Event::class)->findBy(['organizer' => $profile], ['startsAt' => 'ASC']);

        return $this->render('organizer/events/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/new', name: 'app_organizer_events_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        OrganizerAccess $access,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $access->requireApprovedOrganizer($user);

        $event = new Event($profile, new \DateTimeImmutable('+7 days'));
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bannerFile = $form->get('bannerUpload')->getData();
            if ($bannerFile) {
                $safeName = $slugger->slug(pathinfo($bannerFile->getClientOriginalName(), \PATHINFO_FILENAME));
                $newName = $safeName.'-'.bin2hex(random_bytes(6)).'.'.$bannerFile->guessExtension();

                $targetDir = $this->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'banners';
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }

                try {
                    $bannerFile->move($targetDir, $newName);
                    $event->setBannerPath('var/uploads/banners/'.$newName);
                } catch (FileException) {
                    $this->addFlash('error', 'Banner upload failed.');
                }
            }

            $em->persist($event);
            $em->flush();

            return $this->redirectToRoute('app_organizer_events_index');
        }

        return $this->render('organizer/events/form.html.twig', [
            'title' => 'Create event',
            'form' => $form,
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_organizer_events_edit', methods: ['GET', 'POST'])]
    public function edit(
        Event $event,
        Request $request,
        OrganizerAccess $access,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $access->requireApprovedOrganizer($user);

        $this->denyAccessUnlessGranted(EventVoter::MANAGE, $event);

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bannerFile = $form->get('bannerUpload')->getData();
            if ($bannerFile) {
                $safeName = $slugger->slug(pathinfo($bannerFile->getClientOriginalName(), \PATHINFO_FILENAME));
                $newName = $safeName.'-'.bin2hex(random_bytes(6)).'.'.$bannerFile->guessExtension();

                $targetDir = $this->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'banners';
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }

                try {
                    $bannerFile->move($targetDir, $newName);
                    $event->setBannerPath('var/uploads/banners/'.$newName);
                } catch (FileException) {
                    $this->addFlash('error', 'Banner upload failed.');
                }
            }

            $em->flush();
            return $this->redirectToRoute('app_organizer_events_index');
        }

        return $this->render('organizer/events/form.html.twig', [
            'title' => 'Edit event',
            'form' => $form,
            'event' => $event,
        ]);
    }

    #[Route('/{id}/stats', name: 'app_organizer_events_stats', methods: ['GET'])]
    public function stats(
        Event $event,
        OrganizerAccess $access,
        BookingItemRepository $bookingItems,
        TicketTierRepository $tiers,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $access->requireApprovedOrganizer($user);

        $this->denyAccessUnlessGranted(EventVoter::VIEW_STATS, $event);

        $stats = $bookingItems->getConfirmedStatsForEvent($event);

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

        return $this->render('organizer/events/stats.html.twig', [
            'event' => $event,
            'stats' => $stats,
            'tiers' => $tierRows,
            'remainingByTierId' => $remainingByTierId,
        ]);
    }

    #[Route('/{id}/cancel', name: 'app_organizer_events_cancel', methods: ['POST'])]
    public function cancel(
        Event $event,
        Request $request,
        OrganizerAccess $access,
        EventCancellationService $canceller,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $access->requireApprovedOrganizer($user);

        $this->denyAccessUnlessGranted(EventVoter::CANCEL, $event);

        if (!$this->isCsrfTokenValid('cancel_organizer_event_'.$event->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $refunded = $canceller->cancelAndRefund($event);
        $this->addFlash('success', sprintf('Event cancelled. Refunded %d bookings.', $refunded));

        return $this->redirectToRoute('app_organizer_events_index');
    }
}

