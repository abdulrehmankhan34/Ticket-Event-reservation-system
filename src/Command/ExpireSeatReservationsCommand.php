<?php

namespace App\Command;

use App\Repository\SeatReservationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:expire-reservations',
    description: 'Expires stale pending seat reservations.',
)]
class ExpireSeatReservationsCommand extends Command
{
    public function __construct(private readonly SeatReservationRepository $reservations)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->reservations->expireStale();
        $output->writeln(sprintf('Expired %d reservations.', $count));

        return Command::SUCCESS;
    }
}

