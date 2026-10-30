<?php

declare(strict_types=1);

namespace App\Service\Preview;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Visuels conservés par Radar : téléchargés avec les protections des aperçus, vérifiés sur leurs octets,
 * recadrés au format des cartes. Les cartes n'affichent jamais une image chargée depuis la source.
 */
final readonly class PreviewImageStore
{
    private const int MAX_BYTES = 5 * 1024 * 1024;
    private const int MAX_DIMENSION = 8000;
    /** Une image décodée coûte 4 octets par pixel : 16 Mpx tiennent en ~64 Mo, quelle que soit la compression. */
    private const int MAX_PIXELS = 4096 * 4096;
    private const array MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];

    public function __construct(
        #[Autowire(service: 'app.preview_http_client')]
        private HttpClientInterface $httpClient,
        private FilesystemOperator $mediaStorage,
        private LoggerInterface $logger,
    ) {}

    public static function previewPath(string $url): string
    {
        return 'previews/' . hash('sha256', $url) . '.webp';
    }

    public static function faviconPath(string $url): string
    {
        return 'favicons/' . hash('sha256', $url) . '.png';
    }

    /** Image d'aperçu recadrée en 1.91:1 sur 600 px de large, `null` si elle n'a pas pu être conservée. */
    public function storePreview(?string $url): ?string
    {
        if (null === $url) {
            return null;
        }

        $image = $this->download($url);

        return null === $image
            ? null
            : $this->write(self::previewPath($url), ImageCropper::cover($image, 600, 314), 'webp');
    }

    /** Favicon carrée de 64 px. */
    public function storeFavicon(?string $url): ?string
    {
        if (null === $url) {
            return null;
        }

        $image = $this->download($url);

        return null === $image
            ? null
            : $this->write(self::faviconPath($url), ImageCropper::cover($image, 64, 64), 'png');
    }

    private function download(string $url): ?\GdImage
    {
        try {
            $bytes = $this->httpClient->request('GET', $url, [
                'headers' => ['Accept' => 'image/avif,image/webp,image/png,image/jpeg,image/gif'],
                'on_progress' => static function (int $downloaded): void {
                    if ($downloaded > self::MAX_BYTES) {
                        throw new \RuntimeException('Image trop volumineuse.');
                    }
                },
            ])->getContent();
        } catch (ExceptionInterface|\RuntimeException $exception) {
            $this->logger->info('Image d\'aperçu indisponible pour {url} : {message}', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        return $this->decode($bytes);
    }

    /**
     * Le type annoncé par le serveur ne compte pas : seuls les octets font foi. Les dimensions sont lues dans l'en-tête
     * avant décodage, une image très compressée pouvant décoder en centaines de Mo (bombe de pixels).
     */
    private function decode(string $bytes): ?\GdImage
    {
        if (!in_array(new \finfo(FILEINFO_MIME_TYPE)->buffer($bytes), self::MIME_TYPES, strict: true)) {
            return null;
        }

        $size = getimagesizefromstring($bytes);
        if (
            false === $size
            || $size[0] > self::MAX_DIMENSION
            || $size[1] > self::MAX_DIMENSION
            || ($size[0] * $size[1]) > self::MAX_PIXELS
        ) {
            return null;
        }

        // Des octets corrompus déclenchent un avertissement GD en plus du `false` : seul le `false` compte ici.
        set_error_handler(static fn(): bool => true);

        try {
            $image = imagecreatefromstring($bytes);
        } finally {
            restore_error_handler();
        }

        return false === $image ? null : $image;
    }

    /** @param 'webp'|'png' $format */
    private function write(string $path, \GdImage $image, string $format): ?string
    {
        ob_start();
        'webp' === $format ? imagewebp($image, quality: 82) : imagepng($image, quality: 9);
        $encoded = (string) ob_get_clean();

        try {
            $this->mediaStorage->write($path, $encoded);
        } catch (FilesystemException $exception) {
            $this->logger->error('Visuel non enregistré ({path}) : {message}', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        return $path;
    }
}
