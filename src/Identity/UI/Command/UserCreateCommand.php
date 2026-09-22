<?php

namespace App\Identity\UI\Command;

use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\CompanyRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:user:create',
    description: 'Crée un utilisateur (ex. le premier compte admin/validateur de la plateforme).',
)]
class UserCreateCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse email de connexion')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe en clair (sera haché)')
            ->addArgument('firstName', InputArgument::OPTIONAL, 'Prénom', 'Admin')
            ->addArgument('lastName', InputArgument::OPTIONAL, 'Nom', 'Grossmp')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, sprintf(
                'Rôle attribué (%s)',
                implode(', ', User::AVAILABLE_ROLES)
            ), User::ROLE_VALIDATOR)
            ->addOption('company', 'c', InputOption::VALUE_REQUIRED, 'ID de la société cliente à rattacher (optionnel, ex. pour ROLE_BUYER/ROLE_COMPANY_ADMIN)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $plainPassword = $input->getArgument('password');
        $role = strtoupper($input->getOption('role'));
        $companyId = $input->getOption('company');

        if (!in_array($role, User::AVAILABLE_ROLES, true)) {
            $io->error(sprintf('Rôle invalide "%s". Rôles disponibles : %s.', $role, implode(', ', User::AVAILABLE_ROLES)));

            return Command::FAILURE;
        }

        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $io->error(sprintf('Un utilisateur existe déjà avec l\'email "%s".', $email));

            return Command::FAILURE;
        }

        $company = null;
        if (null !== $companyId) {
            $company = $this->companyRepository->findById((int) $companyId);
            if (!$company) {
                $io->error(sprintf('Aucune société trouvée avec l\'ID "%s".', $companyId));

                return Command::FAILURE;
            }
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($input->getArgument('firstName'));
        $user->setLastName($input->getArgument('lastName'));
        $user->setRoles([$role]);
        $user->setCompany($company);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            foreach ($errors as $violation) {
                $io->error($violation->getMessage());
            }

            return Command::FAILURE;
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Utilisateur "%s" créé avec le rôle %s.', $email, $role));

        return Command::SUCCESS;
    }
}
