<?php

namespace App\Command;

use App\Repository\SeatReservationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:expire-reservations:worker',
    description: 'Runs a small worker that periodically expires stale seat reservations.',
)]
class ExpireSeatReservationsWorkerCommand extends Command
{
    public function __construct(private readonly SeatReservationRepository $reservations)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('interval', null, InputOption::VALUE_REQUIRED, 'Interval in seconds between cleanup runs', 60)
            ->addOption('once', null, InputOption::VALUE_NONE, 'Run a single cleanup pass then exit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $interval = max(5, (int) $input->getOption('interval'));
        $once = (bool) $input->getOption('once');

        do {
            $count = $this->reservations->expireStale();
            $output->writeln(sprintf('[%s] Expired %d reservations.', (new \DateTimeImmutable())->format('Y-m-d H:i:s'), $count));

            if ($once) {
                break;
            }

            sleep($interval);
        } while (true);

        return Command::SUCCESS;
    }
}

