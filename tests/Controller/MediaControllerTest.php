<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Service\Preview\LinkBannerStore;
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

    public function testServesBannerToAnonymousVisitorWithPublicCache(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $name = str_repeat('a', times: 64);
        $this->storage()->write(LinkBannerStore::path($name), 'JPEG');

        $client->request('GET', sprintf('/annonces/bandeaux/%s.jpg', $name));

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'image/jpeg');
        self::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        $cacheControl = (string) $client->getResponse()->headers->get('Cache-Control');
        self::assertStringContainsString('public', $cacheControl);
        self::assertStringContainsString('max-age=86400', $cacheControl);
        self::assertStringNotContainsString('immutable', $cacheControl);
    }

    public function testAnswersNotFoundForMissingBanner(): void
    {
        $client = self::createClient();

        $client->request('GET', sprintf('/annonces/bandeaux/%s.jpg', str_repeat('b', times: 64)));

        self::assertResponseStatusCodeSame(404);
    }

    public function testServesOnlyBannersWithoutLogin(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $path = PreviewImageStore::previewPath('https://example.com/prive.png');
        $this->storage()->write($path, 'WEBP');

        $client->request('GET', '/annonces/bandeaux/' . $path);
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', sprintf('/annonces/bandeaux/%s.jpg', str_repeat('A', times: 64)));
        self::assertResponseStatusCodeSame(404);
    }

    private function storage(): FilesystemOperator
    {
        $storage = self::getContainer()->get('media.storage');
        self::assertInstanceOf(FilesystemOperator::class, $storage);

        return $storage;
    }
}
