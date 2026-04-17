<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates the single admin account (ROLE_ADMIN).',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Admin email')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Admin password (min 8 chars)')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite existing user if email already exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) $input->getOption('email');
        $password = (string) $input->getOption('password');
        $force = (bool) $input->getOption('force');

        if ($email === '' || $password === '') {
            $output->writeln('<error>Both --email and --password are required.</error>');
            return Command::INVALID;
        }

        if (mb_strlen($password) < 8) {
            $output->writeln('<error>Password must be at least 8 characters.</error>');
            return Command::INVALID;
        }

        $repo = $this->entityManager->getRepository(User::class);
        $existing = $repo->findOneBy(['email' => $email]);

        if ($existing instanceof User && !$force) {
            $output->writeln('<error>User already exists. Use --force to overwrite roles/password.</error>');
            return Command::FAILURE;
        }

        $user = $existing instanceof User ? $existing : new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $violations = $this->validator->validate($user);
        if (\count($violations) > 0) {
            foreach ($violations as $violation) {
                $output->writeln('<error>'.$violation->getPropertyPath().': '.$violation->getMessage().'</error>');
            }

            return Command::FAILURE;
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Admin user created/updated.</info>');
        return Command::SUCCESS;
    }
}

