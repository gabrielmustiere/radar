<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\User;
use App\Enum\Type\Team;
use App\Repository\LinkRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class CategoryNav
{
    public ?int $currentId = null;

    public function __construct(
        private readonly LinkRepository $linkRepository,
        private readonly Security $security,
    ) {}

    /**
     * Les catégories de l'équipe du membre d'abord (`mine`, vide s'il n'a pas d'équipe), puis toutes les autres.
     *
     * @return array{
     *     mine: list<array{id: int, name: string, team: ?Team, linkCount: int}>,
     *     others: list<array{id: int, name: string, team: ?Team, linkCount: int}>,
     * }
     */
    public function getSections(): array
    {
        $user = $this->security->getUser();
        $team = $user instanceof User ? $user->getTeam() : null;

        $sections = ['mine' => [], 'others' => []];
        foreach ($this->sortedCategories() as $category) {
            $sections[null !== $team && $category['team'] === $team ? 'mine' : 'others'][] = $category;
        }

        return $sections;
    }

    /**
     * Ordre alphabétique français : SQLite trie à l'octet (majuscules avant minuscules, accents après « z », « 10 » avant « 2 »).
     *
     * @return list<array{id: int, name: string, team: ?Team, linkCount: int}>
     */
    private function sortedCategories(): array
    {
        $collator = new \Collator('fr_FR');
        $collator->setAttribute(\Collator::NUMERIC_COLLATION, \Collator::ON);

        $categories = $this->linkRepository->countByCategory();
        usort(
            $categories,
            /**
             * @param array{id: int, name: string, team: ?Team, linkCount: int} $a
             * @param array{id: int, name: string, team: ?Team, linkCount: int} $b
             */
            static fn(array $a, array $b): int => (int) $collator->compare($a['name'], $b['name']),
        );

        return $categories;
    }
}
