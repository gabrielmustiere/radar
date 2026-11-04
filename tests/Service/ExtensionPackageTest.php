<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ExtensionPackage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class ExtensionPackageTest extends TestCase
{
    private const string SOURCE = __DIR__ . '/../../extension';

    private ?string $temporarySource = null;

    #[\Override]
    protected function tearDown(): void
    {
        if (null !== $this->temporarySource) {
            new Filesystem()->remove($this->temporarySource);
        }
    }

    public function testPackageContainsEverySourceFile(): void
    {
        $names = array_keys(self::unzip(self::package()->zip()));

        foreach ([
            'background.js',
            'config.js',
            'icons/icon-128.png',
            'manifest.json',
            'popup.html',
            'popup.js',
        ] as $name) {
            self::assertContains($name, $names);
        }
    }

    public function testPackageLeavesHiddenFilesOut(): void
    {
        $source = $this->temporarySource("export const RADAR_URL = 'https://radar.wip';\n");
        file_put_contents($source . '/.DS_Store', data: 'macOS');

        $names = array_keys(self::unzip(new ExtensionPackage($source, 'https://radar.example', 'cle-publique')->zip()));

        self::assertNotContains('.DS_Store', $names);
    }

    public function testPackagedManifestGainsThePublicKeyAndKeepsTheSourceVersion(): void
    {
        $source = self::decode((string) file_get_contents(self::SOURCE . '/manifest.json'));
        $packaged = self::decode(self::unzip(self::package()->zip())['manifest.json'] ?? '');

        self::assertSame('cle-publique', $packaged['key'] ?? null);
        self::assertSame($source['version'] ?? null, $packaged['version'] ?? null);
        self::assertSame($source['version'] ?? null, self::package()->version());
        self::assertArrayNotHasKey('key', $source);
    }

    public function testConfigTargetsTheConfiguredAddress(): void
    {
        $config = self::unzip(self::package('https://radar.example/')->zip())['config.js'] ?? '';

        self::assertStringContainsString("'https://radar.example'", $config);
        self::assertStringNotContainsString('radar.wip', $config);
    }

    public function testConfigWithoutTheLocalAddressIsRefused(): void
    {
        $package = new ExtensionPackage(
            $this->temporarySource("export const RADAR_URL = 'https://ailleurs.example';\n"),
            'https://radar.example',
            'cle-publique',
        );

        $this->expectException(\LogicException::class);

        $package->zip();
    }

    private static function package(string $radarUrl = 'https://radar.example'): ExtensionPackage
    {
        return new ExtensionPackage(self::SOURCE, $radarUrl, 'cle-publique');
    }

    /** @return array{key?: string, version?: string, ...} */
    private static function decode(string $json): array
    {
        /** @var array{key?: string, version?: string, ...} */
        return json_decode($json, associative: true, flags: \JSON_THROW_ON_ERROR);
    }

    private function temporarySource(string $config): string
    {
        $source = sys_get_temp_dir() . '/radar-extension-test-' . bin2hex(random_bytes(4));
        mkdir($source);
        $this->temporarySource = $source;
        file_put_contents($source . '/config.js', $config);
        file_put_contents($source . '/manifest.json', data: '{"version": "1.0.0"}');

        return $source;
    }

    /** @return array<string, string> */
    private static function unzip(string $bytes): array
    {
        $path = (string) tempnam(sys_get_temp_dir(), prefix: 'radar-extension-test');
        file_put_contents($path, $bytes);
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($path));

        $files = [];
        for ($index = 0; $index < $zip->numFiles; ++$index) {
            $files[(string) $zip->getNameIndex($index)] = (string) $zip->getFromIndex($index);
        }
        $zip->close();
        unlink($path);

        return $files;
    }
}
