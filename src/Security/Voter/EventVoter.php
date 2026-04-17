<?php

namespace App\Security\Voter;

use App\Entity\Event;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class EventVoter extends Voter
{
    public const MANAGE = 'EVENT_MANAGE';
    public const VIEW_STATS = 'EVENT_VIEW_STATS';
    public const CANCEL = 'EVENT_CANCEL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::MANAGE, self::VIEW_STATS, self::CANCEL], true)) {
            return false;
        }

        return $subject instanceof Event;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Event management is organizer-only.
        if (!in_array(User::ROLE_ORGANIZER, $user->getRoles(), true)) {
            return false;
        }

        /** @var Event $event */
        $event = $subject;

        // Owner check.
        return $event->getOrganizer()->getUser()->getId() === $user->getId();
    }
}

