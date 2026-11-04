<?php

declare(strict_types=1);

namespace App\Service\Preview;

use App\Entity\Link;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Bandeaux 3:1 des annonces Chat, tirés de l'aperçu conservé. Google les lit sans compte : seul leur nom les protège,
 * d'où un HMAC propre à chaque lien plutôt qu'un hash de l'image, et leur retrait à la suppression du lien.
 */
final readonly class LinkBannerStore
{
    public function __construct(
        private FilesystemOperator $mediaStorage,
        private LoggerInterface $logger,
        #[Autowire('%kernel.secret%')]
        #[\SensitiveParameter]
        private string $secret,
    ) {}

    public static function path(string $name): string
    {
        return 'banners/' . $name . '.jpg';
    }

    public function name(Link $link): string
    {
        return hash_hmac('sha256', (string) $link->getId(), $this->secret);
    }

    /** @return ?string le nom du bandeau, `null` si le lien n'a pas d'aperçu conservé ou si l'écriture a échoué */
    public function store(Link $link): ?string
    {
        $previewPath = $link->getImagePath();
        if (null === $previewPath) {
            return null;
        }

        $name = $this->name($link);

        try {
            $preview = $this->decode($this->mediaStorage->read($previewPath));
            if (false === $preview) {
                return null;
            }

            ob_start();
            imagejpeg(ImageCropper::onWhite(ImageCropper::cover($preview, 600, 200)), quality: 82);
            $this->mediaStorage->write(self::path($name), (string) ob_get_clean());
        } catch (FilesystemException $exception) {
            $this->logger->error('Bandeau non enregistré pour {path} : {message}', [
                'path' => $previewPath,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        return $name;
    }

    private function decode(string $bytes): \GdImage|false
    {
        // Des octets corrompus déclenchent un avertissement GD en plus du `false` : seul le `false` compte ici.
        set_error_handler(static fn(): bool => true);

        try {
            return imagecreatefromstring($bytes);
        } finally {
            restore_error_handler();
        }
    }

    public function remove(Link $link): void
    {
        $path = self::path($this->name($link));

        try {
            $this->mediaStorage->delete($path);
        } catch (FilesystemException $exception) {
            $this->logger->error('Bandeau non supprimé ({path}) : {message}', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
