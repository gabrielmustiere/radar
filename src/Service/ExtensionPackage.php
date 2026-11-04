<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Paquet de l'extension téléchargé depuis Radar : il vise `DEFAULT_URI`, et sa `key` lui donne la même identité dans
 * tout dossier, faute de quoi charger une nouvelle version ailleurs perdrait la clé du membre. Cette `key` reste hors
 * du dépôt : l'extension locale garde son identité propre, et le Web Store refuse ce champ à l'envoi.
 */
final readonly class ExtensionPackage
{
    private const string LOCAL_URL = 'https://radar.wip';

    public function __construct(
        #[Autowire('%kernel.project_dir%/extension')]
        private string $sourceDirectory,
        #[Autowire('%env(DEFAULT_URI)%')]
        private string $radarUrl,
        #[Autowire('%app.extension_public_key%')]
        private string $publicKey,
    ) {}

    public function version(): string
    {
        return $this->manifest()['version'];
    }

    public function zip(): string
    {
        $path = tempnam(sys_get_temp_dir(), prefix: 'radar-extension');
        if (false === $path) {
            throw new \RuntimeException('Fichier temporaire du paquet impossible à créer.');
        }

        try {
            $zip = new \ZipArchive();
            if (true !== $zip->open($path, \ZipArchive::OVERWRITE)) {
                throw new \RuntimeException('Paquet de l\'extension impossible à ouvrir.');
            }

            foreach ($this->sourceFiles() as $name => $source) {
                $zip->addFromString($name, match ($name) {
                    'manifest.json' => $this->packagedManifest(),
                    'config.js' => $this->packagedConfig($source),
                    default => self::read($source),
                });
            }
            $zip->close();

            return self::read($path);
        } finally {
            unlink($path);
        }
    }

    /** @return array<string, string> Chemin dans le paquet => chemin source */
    private function sourceFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->sourceDirectory, \FilesystemIterator::SKIP_DOTS),
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (str_starts_with($file->getFilename(), '.')) {
                continue;
            }

            $files[substr($file->getPathname(), strlen($this->sourceDirectory) + 1)] = $file->getPathname();
        }

        return $files;
    }

    /** @return array{version: string, ...} */
    private function manifest(): array
    {
        /** @var array{version: string, ...} */
        return json_decode(
            self::read($this->sourceDirectory . '/manifest.json'),
            associative: true,
            flags: \JSON_THROW_ON_ERROR,
        );
    }

    private function packagedManifest(): string
    {
        return json_encode(
            ['key' => $this->publicKey] + $this->manifest(),
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR,
        );
    }

    private function packagedConfig(string $source): string
    {
        $config = self::read($source);
        if (!str_contains($config, self::LOCAL_URL)) {
            throw new \LogicException('Adresse locale absente du config.js de l\'extension.');
        }

        return str_replace(self::LOCAL_URL, rtrim($this->radarUrl, characters: '/'), $config);
    }

    private static function read(string $path): string
    {
        $content = file_get_contents($path);
        if (false === $content) {
            throw new \RuntimeException(sprintf('Fichier illisible : %s.', $path));
        }

        return $content;
    }
}
