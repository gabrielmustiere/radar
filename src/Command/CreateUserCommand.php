<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\EmailAlreadyUsedException;
use App\Service\UserManager;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:create',
    description: 'Crée un compte membre ; le mot de passe est demandé en saisie masquée, ou lu sur l\'entrée standard.',
)]
final readonly class CreateUserCommand
{
    public function __construct(
        private UserManager $userManager,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        InputInterface $input,
        #[Argument(description: 'Email de connexion du membre')]
        string $email,
        #[Option(
            description: 'Lit le mot de passe sur l\'entrée standard (déploiement), sans saisie ni trace dans la ligne de commande',
        )]
        bool $passwordStdin = false,
    ): int {
        $email = trim($email);
        if ('' === $email || false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error(sprintf('« %s » n\'est pas un email valide.', $email));

            return Command::INVALID;
        }

        $password = $passwordStdin ? $this->readPassword($input) : (string) $io->askHidden('Mot de passe');
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

    private function readPassword(InputInterface $input): string
    {
        $stream = ($input instanceof StreamableInputInterface ? $input->getStream() : null) ?? STDIN;

        return rtrim((string) fgets($stream), characters: "\r\n");
    }
}
