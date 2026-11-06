<?php

declare(strict_types=1);

namespace App\Service\Preview;

use App\Entity\Link;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Bandeaux des annonces Chat déjà envoyées : plus aucun n'est créé, mais les anciens messages y pointent encore.
 * Google les lit sans compte : seul leur nom les protège, d'où un HMAC propre à chaque lien et leur retrait à la
 * suppression du lien.
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
