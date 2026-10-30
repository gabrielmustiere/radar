<?php

declare(strict_types=1);

namespace App\Service\Preview\Extractor;

use App\Service\Preview\GitHubRepository;
use App\Service\Preview\HtmlPage;
use App\Service\Preview\JsonFetcher;
use App\Service\Preview\LinkPreview;
use App\Service\Preview\MetadataValue;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Étoiles, langage et description d'un dépôt par l'API REST de GitHub, seulement si un jeton est configuré :
 * sans jeton, l'aperçu OpenGraph de la page suffit.
 */
#[AsTaggedItem(priority: 500)]
final readonly class GitHubExtractor implements MetadataExtractor
{
    public function __construct(
        private JsonFetcher $jsonFetcher,
        #[Autowire(env: 'GITHUB_TOKEN')]
        #[\SensitiveParameter]
        private string $token,
    ) {}

    #[\Override]
    public function extract(string $url, ?HtmlPage $page): LinkPreview
    {
        $repository = GitHubRepository::fromUrl($url);
        if ('' === $this->token || null === $repository) {
            return new LinkPreview();
        }

        $data = $this->jsonFetcher->fetch('https://api.github.com/repos/' . $repository->name, [
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer ' . $this->token,
            'X-GitHub-Api-Version' => '2022-11-28',
        ]) ?? [];

        // Sur une issue, une PR ou un fichier, le titre et la description de la page (OpenGraph) priment sur ceux du dépôt.
        return new LinkPreview(
            title: $repository->isRoot ? MetadataValue::text($data['full_name'] ?? null) : null,
            description: $repository->isRoot ? MetadataValue::text($data['description'] ?? null) : null,
            siteName: 'GitHub',
            githubStars: MetadataValue::int($data['stargazers_count'] ?? null),
            githubLanguage: MetadataValue::text($data['language'] ?? null),
        );
    }
}
