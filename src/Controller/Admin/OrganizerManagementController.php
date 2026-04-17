<?php

namespace App\Controller\Admin;

use App\Entity\OrganizerProfile;
use App\Entity\User;
use App\Repository\OrganizerProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/organizers')]
class OrganizerManagementController extends AbstractController
{
    #[Route('', name: 'app_admin_organizers_index', methods: ['GET'])]
    public function index(OrganizerProfileRepository $organizerProfiles): Response
    {
        return $this->render('admin/organizers/index.html.twig', [
            'pending' => $organizerProfiles->findPending(),
            'all' => $organizerProfiles->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/approve', name: 'app_admin_organizers_approve', methods: ['POST'])]
    public function approve(
        OrganizerProfile $profile,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('approve_org_'.$profile->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $profile->setApprovalStatus(OrganizerProfile::STATUS_APPROVED);
        $profile->setIsActive(true);

        $profile->getUser()->addRole(User::ROLE_ORGANIZER);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_organizers_index');
    }

    #[Route('/{id}/reject', name: 'app_admin_organizers_reject', methods: ['POST'])]
    public function reject(
        OrganizerProfile $profile,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('reject_org_'.$profile->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $profile->setApprovalStatus(OrganizerProfile::STATUS_REJECTED);
        $profile->getUser()->removeRole(User::ROLE_ORGANIZER);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_organizers_index');
    }

    #[Route('/{id}/toggle-active', name: 'app_admin_organizers_toggle_active', methods: ['POST'])]
    public function toggleActive(
        OrganizerProfile $profile,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('toggle_org_'.$profile->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $newActive = !$profile->isActive();
        $profile->setIsActive($newActive);

        if (!$newActive) {
            $profile->getUser()->removeRole(User::ROLE_ORGANIZER);
        } elseif ($profile->isApproved()) {
            $profile->getUser()->addRole(User::ROLE_ORGANIZER);
        }
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_organizers_index');
    }
}

