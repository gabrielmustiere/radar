<?php

declare(strict_types=1);

namespace App\Service\Preview;

/**
 * Dépôt désigné par une adresse github.com : sa racine, ou une page interne (issue, PR, fichier…).
 */
final readonly class GitHubRepository
{
    /** Premiers segments d'adresse GitHub qui ne désignent pas un propriétaire de dépôt. */
    private const array RESERVED_OWNERS = [
        'about',
        'collections',
        'enterprise',
        'explore',
        'features',
        'marketplace',
        'orgs',
        'pricing',
        'settings',
        'sponsors',
        'topics',
        'trending',
    ];

    private function __construct(
        public string $name,
        public bool $isRoot,
    ) {}

    public static function fromUrl(string $url): ?self
    {
        if (!in_array(
            strtolower((string) parse_url($url, PHP_URL_HOST)),
            ['github.com', 'www.github.com'],
            strict: true,
        )) {
            return null;
        }

        $matches = [];
        if (1 !== preg_match('#^/([\w.-]+)/([\w.-]+)(/.*)?$#', (string) parse_url($url, PHP_URL_PATH), $matches)) {
            return null;
        }

        $owner = $matches[1] ?? '';
        $name = $matches[2] ?? '';
        // `..` renverrait l'appel authentifié vers une autre ressource de l'API.
        if (
            in_array(strtolower($owner), self::RESERVED_OWNERS, strict: true)
            || [] !== array_intersect([$owner, $name], ['.', '..'])
        ) {
            return null;
        }

        $name = str_ends_with($name, '.git') ? substr($name, offset: 0, length: -4) : $name;

        return new self($owner . '/' . $name, in_array($matches[3] ?? '', ['', '/'], strict: true));
    }
}
