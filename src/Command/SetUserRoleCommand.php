<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\UserRoles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:set-role',
    description: 'Attribue un rôle à un utilisateur existant (par défaut : super admin)',
)]
final class SetUserRoleCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, "Email de l'utilisateur")
            ->addArgument('role', InputArgument::OPTIONAL, 'user, admin ou super_admin', UserRoles::SUPER_ADMIN)
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'Affiche les utilisateurs et leur rôle')
            ->setHelp(<<<'HELP'
                Exemples :
                  <info>php %command.full_name% --list</info>
                  <info>php %command.full_name% jean@exemple.fr</info>              (super admin)
                  <info>php %command.full_name% jean@exemple.fr admin</info>
                  <info>php %command.full_name% jean@exemple.fr user</info>         (retour organisateur)
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('list')) {
            $this->listUsers($io);

            return Command::SUCCESS;
        }

        $email = $input->getArgument('email');
        if (null === $email) {
            if (!$input->isInteractive()) {
                $io->error("Précisez l'email de l'utilisateur.");

                return Command::INVALID;
            }
            $this->listUsers($io);
            $email = $io->ask("Email de l'utilisateur");
        }

        $role = $this->normalizeRole((string) $input->getArgument('role'));
        if (null === $role) {
            $io->error('Rôle inconnu. Valeurs possibles : user, admin, super_admin.');

            return Command::INVALID;
        }

        $user = $this->userRepository->findOneBy(['email' => trim((string) $email)]);
        if (!$user instanceof User) {
            $io->error(sprintf('Aucun utilisateur avec l\'email "%s". Utilisez --list pour voir les comptes.', $email));

            return Command::FAILURE;
        }

        $currentRole = UserRoles::highest($user);
        if ($currentRole === $role) {
            $io->note(sprintf('%s est déjà %s.', $user->getEmail(), UserRoles::LABELS[$role]));

            return Command::SUCCESS;
        }

        // Ne jamais laisser l'application sans super admin
        if (UserRoles::SUPER_ADMIN === $currentRole && 1 >= count($this->findSuperAdmins())) {
            $io->error(sprintf('%s est le dernier super admin : nommez-en un autre avant de le rétrograder.', $user->getEmail()));

            return Command::FAILURE;
        }

        $question = sprintf(
            '%s %s : %s -> %s. Confirmer ?',
            $user->getFirstname(),
            $user->getLastname(),
            UserRoles::LABELS[$currentRole],
            UserRoles::LABELS[$role]
        );
        if (!$io->confirm($question, true)) {
            $io->warning('Aucune modification.');

            return Command::SUCCESS;
        }

        $user->setRoles(UserRoles::toStoredRoles($role));
        $this->entityManager->flush();

        $io->success(sprintf('%s est maintenant %s.', $user->getEmail(), UserRoles::LABELS[$role]));
        $io->comment("S'il était connecté, il devra se reconnecter pour que le changement s'applique.");

        return Command::SUCCESS;
    }

    private function normalizeRole(string $role): ?string
    {
        $role = strtoupper(trim($role));
        if (!str_starts_with($role, 'ROLE_')) {
            $role = 'ROLE_' . $role;
        }

        return array_key_exists($role, UserRoles::LABELS) ? $role : null;
    }

    /** @return User[] */
    private function findSuperAdmins(): array
    {
        return array_filter(
            $this->userRepository->findAll(),
            fn (User $u) => UserRoles::SUPER_ADMIN === UserRoles::highest($u)
        );
    }

    private function listUsers(SymfonyStyle $io): void
    {
        $rows = array_map(
            fn (User $u) => [$u->getId(), $u->getEmail(), $u->getFirstname() . ' ' . $u->getLastname(), UserRoles::LABELS[UserRoles::highest($u)]],
            $this->userRepository->findBy([], ['id' => 'ASC'])
        );

        $io->table(['ID', 'Email', 'Nom', 'Rôle'], $rows);
    }
}
