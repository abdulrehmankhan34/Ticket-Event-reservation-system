<?php

namespace App\Security;

use App\Entity\OrganizerProfile;
use App\Entity\User;
use App\Repository\OrganizerProfileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Small helper to enforce organizer approval/ownership checks.
 */
final class OrganizerAccess
{
    public function __construct(private readonly OrganizerProfileRepository $organizerProfiles)
    {
    }

    public function requireApprovedOrganizer(User $user): OrganizerProfile
    {
        $profile = $this->organizerProfiles->findOneByUser($user);

        if (!$profile) {
            throw new AccessDeniedHttpException('Organizer profile not found. Apply as organizer first.');
        }
        if (!$profile->isActive()) {
            throw new AccessDeniedHttpException('Organizer account is deactivated.');
        }
        if (!$profile->isApproved()) {
            throw new AccessDeniedHttpException('Organizer account is not approved yet.');
        }

        return $profile;
    }
}

