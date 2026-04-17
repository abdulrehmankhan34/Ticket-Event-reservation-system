<?php

namespace App\Command;

use App\Entity\OrganizerProfile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sync-organizer-roles',
    description: 'Backfills/removes ROLE_ORGANIZER based on organizer profile approval/active status.',
)]
class SyncOrganizerRolesCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $profiles = $this->entityManager->getRepository(OrganizerProfile::class)->findAll();

        $added = 0;
        $removed = 0;

        foreach ($profiles as $profile) {
            if (!$profile instanceof OrganizerProfile) {
                continue;
            }

            $user = $profile->getUser();
            $shouldHave = $profile->isActive() && $profile->isApproved();
            $has = in_array(User::ROLE_ORGANIZER, $user->getRoles(), true);

            if ($shouldHave && !$has) {
                $user->addRole(User::ROLE_ORGANIZER);
                $added++;
            } elseif (!$shouldHave && $has) {
                $user->removeRole(User::ROLE_ORGANIZER);
                $removed++;
            }
        }

        $this->entityManager->flush();

        $output->writeln(sprintf('ROLE_ORGANIZER added=%d removed=%d', $added, $removed));
        return Command::SUCCESS;
    }
}

