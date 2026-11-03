<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\EmailAlreadyUsedException;
use App\Service\UserManager;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:create',
    description: 'Crée un compte membre ; le mot de passe est demandé en saisie masquée.',
)]
final readonly class CreateUserCommand
{
    public function __construct(
        private UserManager $userManager,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Email de connexion du membre')]
        string $email,
    ): int {
        $email = trim($email);
        if ('' === $email || false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error(sprintf('« %s » n\'est pas un email valide.', $email));

            return Command::INVALID;
        }

        $password = (string) $io->askHidden('Mot de passe');
        if ('' === $password) {
            $io->error('Le mot de passe ne peut pas être vide.');

            return Command::INVALID;
        }

        try {
            $this->userManager->create($email, $password, team: null, admin: false);
        } catch (EmailAlreadyUsedException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Compte créé pour %s.', $email));

        return Command::SUCCESS;
    }
}
