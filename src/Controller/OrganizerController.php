<?php

namespace App\Controller;

use App\Entity\OrganizerProfile;
use App\Entity\User;
use App\Repository\OrganizerProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/organizer')]
class OrganizerController extends AbstractController
{
    #[Route('/apply', name: 'app_organizer_apply')]
    public function apply(
        OrganizerProfileRepository $organizerProfiles,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $existing = $organizerProfiles->findOneByUser($user);
        if ($existing instanceof OrganizerProfile) {
            return $this->redirectToRoute('app_organizer_dashboard');
        }

        $profile = new OrganizerProfile($user);
        $entityManager->persist($profile);
        $entityManager->flush();

        return $this->redirectToRoute('app_organizer_dashboard');
    }

    #[Route('/dashboard', name: 'app_organizer_dashboard')]
    public function dashboard(OrganizerProfileRepository $organizerProfiles): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $profile = $organizerProfiles->findOneByUser($user);

        return $this->render('organizer/dashboard.html.twig', [
            'profile' => $profile,
        ]);
    }

    #[Route('', name: 'app_organizer_home', methods: ['GET'])]
    public function home(OrganizerProfileRepository $organizerProfiles): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $profile = $organizerProfiles->findOneByUser($user);
        if (!$profile) {
            return $this->redirectToRoute('app_organizer_dashboard');
        }

        // Placeholder: In later phases, organizer CRUD will live here.
        if (!$profile->isActive()) {
            throw $this->createAccessDeniedException('Organizer account is deactivated.');
        }
        if (!$profile->isApproved()) {
            throw $this->createAccessDeniedException('Organizer account is not approved yet.');
        }

        return new Response('Organizer area (coming soon).');
    }
}

