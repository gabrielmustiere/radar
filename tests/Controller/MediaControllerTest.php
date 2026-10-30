<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Service\Preview\PreviewImageStore;
use League\Flysystem\FilesystemOperator;

final class MediaControllerTest extends LinkWebTestCase
{
    public function testServesStoredVisualToMembersWithLongPrivateCache(): void
    {
        $client = $this->loggedClient();
        $client->disableReboot();
        $path = PreviewImageStore::previewPath('https://example.com/cover.png');
        $this->storage()->write($path, 'WEBP');

        $client->request('GET', '/media/' . $path);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'image/webp');
        self::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        $cacheControl = (string) $client->getResponse()->headers->get('Cache-Control');
        self::assertStringContainsString('private', $cacheControl);
        self::assertStringContainsString('max-age=31536000', $cacheControl);
        self::assertStringContainsString('immutable', $cacheControl);
    }

    public function testRedirectsAnonymousVisitorToLogin(): void
    {
        $client = self::createClient();

        $client->request('GET', '/media/' . PreviewImageStore::previewPath('https://example.com/cover.png'));

        self::assertResponseRedirects('/login');
    }

    public function testAnswersNotFoundForMissingVisual(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/media/' . PreviewImageStore::faviconPath('https://example.com/absente.png'));

        self::assertResponseStatusCodeSame(404);
    }

    public function testRefusesPathOutsideStoredVisuals(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/media/previews/../../../.env');

        self::assertResponseStatusCodeSame(404);
    }

    private function storage(): FilesystemOperator
    {
        $storage = self::getContainer()->get('media.storage');
        self::assertInstanceOf(FilesystemOperator::class, $storage);

        return $storage;
    }
}
