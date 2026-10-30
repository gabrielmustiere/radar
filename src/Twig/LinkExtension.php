<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\Preview\GitHubRepository;
use App\Service\Preview\YouTubeVideoId;
use Twig\Attribute\AsTwigFilter;

final readonly class LinkExtension
{
    #[AsTwigFilter('link_host')]
    public function host(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return str_starts_with($host, 'www.') ? substr($host, offset: 4) : $host;
    }

    #[AsTwigFilter('youtube_id')]
    public function youTubeId(string $url): ?string
    {
        return YouTubeVideoId::fromUrl($url);
    }

    /** Dépôt désigné par une adresse GitHub, au format `propriétaire/nom`. */
    #[AsTwigFilter('github_repository')]
    public function gitHubRepository(string $url): ?string
    {
        return GitHubRepository::fromUrl($url)?->name;
    }

    /** Compte d'une adresse X, de post ou de profil : `x.com/karpathy/status/…` → `karpathy` (`/i/…` n'en désigne aucun). */
    #[AsTwigFilter('x_handle')]
    public function xHandle(string $url): ?string
    {
        $matches = [];
        if (1 !== preg_match('#^/(\w{1,15})(?:/|$)#', (string) parse_url($url, PHP_URL_PATH), $matches)) {
            return null;
        }

        $handle = $matches[1] ?? null;

        return 'i' === $handle ? null : $handle;
    }
}
