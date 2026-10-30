<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\RefreshLinkPreview;
use App\Repository\LinkRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Rafraîchissement sans annonce. Le snapshot d'aperçu sert de cache : un aperçu prêt se rafraîchit au bout de 7 jours, un échec se retente après 1 heure.
 */
#[AsCommand(name: 'app:links:enrich', description: '(Re)construit l\'aperçu des liens dont le snapshot est échu.')]
final readonly class EnrichLinksCommand
{
    private const string READY_TTL = '-7 days';
    private const string UNAVAILABLE_TTL = '-1 hour';

    public function __construct(
        private LinkRepository $linkRepository,
        private MessageBusInterface $messageBus,
        private ClockInterface $clock,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Liste les liens concernés sans rien envoyer')]
        bool $dryRun = false,
        #[Option(description: 'Tous les liens, échus ou non')]
        bool $force = false,
    ): int {
        $now = $this->clock->now();
        $links = $force
            ? $this->linkRepository->findBy([], ['id' => 'ASC'])
            : $this->linkRepository->findDueForEnrichment(
                $now->modify(self::READY_TTL),
                $now->modify(self::UNAVAILABLE_TTL),
            );

        if ([] === $links) {
            $io->success('Aucun lien à enrichir.');

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($links as $link) {
            $rows[] = [
                $link->getId(),
                $link->getPreviewStatus()->value,
                $link->getPreviewFetchedAt()?->format('Y-m-d H:i') ?? 'jamais',
                $link->getUrl(),
            ];
        }

        $io->table(['Lien', 'Aperçu', 'Dernière tentative', 'Adresse'], $rows);

        if ($dryRun) {
            $io->note(sprintf('Simulation : %d lien(s) seraient enrichis, rien n\'a été envoyé.', count($links)));

            return Command::SUCCESS;
        }

        foreach ($links as $link) {
            $this->messageBus->dispatch(new RefreshLinkPreview((int) $link->getId()));
        }

        $io->success(sprintf('%d lien(s) envoyés à l\'enrichissement.', count($links)));

        return Command::SUCCESS;
    }
}
