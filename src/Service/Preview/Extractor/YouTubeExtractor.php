<?php

declare(strict_types=1);

namespace App\Service\Preview\Extractor;

use App\Service\Preview\HtmlPage;
use App\Service\Preview\JsonFetcher;
use App\Service\Preview\LinkPreview;
use App\Service\Preview\MetadataValue;
use App\Service\Preview\YouTubeVideoId;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsTaggedItem(priority: 600)]
final readonly class YouTubeExtractor implements MetadataExtractor
{
    public function __construct(
        private JsonFetcher $jsonFetcher,
        #[Autowire(service: 'app.preview_http_client')]
        private HttpClientInterface $httpClient,
    ) {}

    #[\Override]
    public function extract(string $url, ?HtmlPage $page): LinkPreview
    {
        $id = YouTubeVideoId::fromUrl($url);
        if (null === $id) {
            return new LinkPreview();
        }

        // Sans réponse oEmbed, la vidéo est privée, supprimée ou YouTube injoignable : pas d'aperçu plutôt qu'une miniature seule.
        $data = $this->jsonFetcher->fetch(
            'https://www.youtube.com/oembed?format=json&url=' . rawurlencode('https://www.youtube.com/watch?v=' . $id),
        );
        if (null === $data) {
            return new LinkPreview();
        }

        return new LinkPreview(
            title: MetadataValue::text($data['title'] ?? null),
            imageUrl: $this->thumbnail($id),
            siteName: 'YouTube',
            author: MetadataValue::text($data['author_name'] ?? null),
        );
    }

    /** La miniature haute définition n'existe pas pour toutes les vidéos : `hqdefault` est toujours servie. */
    private function thumbnail(string $id): string
    {
        $maxResolution = sprintf('https://i.ytimg.com/vi/%s/maxresdefault.jpg', $id);

        $standard = sprintf('https://i.ytimg.com/vi/%s/hqdefault.jpg', $id);

        try {
            return 200 === $this->httpClient->request('HEAD', $maxResolution)->getStatusCode()
                ? $maxResolution
                : $standard;
        } catch (ExceptionInterface) {
            return $standard;
        }
    }
}
