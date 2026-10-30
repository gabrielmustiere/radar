<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Service\Preview\PreviewImageStore;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PreviewImageStoreTest extends TestCase
{
    private const string IMAGE_URL = 'https://cdn.example.com/cover.png';

    public function testStoresPreviewCroppedToCardFormatAsWebp(): void
    {
        $storage = new Filesystem(new InMemoryFilesystemAdapter());

        $path = $this->store(new MockResponse(TestImage::png(800, 800)), $storage)->storePreview(self::IMAGE_URL);

        self::assertSame(PreviewImageStore::previewPath(self::IMAGE_URL), $path);
        self::assertMatchesRegularExpression('#^previews/[a-f0-9]{64}\.webp$#', $path);
        $size = getimagesizefromstring($storage->read($path));
        self::assertSame([600, 314, 'image/webp'], [$size[0] ?? null, $size[1] ?? null, $size['mime'] ?? null]);
    }

    public function testStoresSquareFaviconAsPng(): void
    {
        $storage = new Filesystem(new InMemoryFilesystemAdapter());

        $path = (string) $this->store(new MockResponse(TestImage::png(180, 180)), $storage)->storeFavicon(
            self::IMAGE_URL,
        );

        $size = getimagesizefromstring($storage->read($path));
        self::assertSame([64, 64, 'image/png'], [$size[0] ?? null, $size[1] ?? null, $size['mime'] ?? null]);
    }

    #[DataProvider('rejectedImages')]
    public function testRejectsWhatIsNotARealReasonableImage(MockResponse $response): void
    {
        $storage = new Filesystem(new InMemoryFilesystemAdapter());

        self::assertNull($this->store($response, $storage)->storePreview(self::IMAGE_URL));
        self::assertSame([], $storage->listContents('/', deep: true)->toArray());
    }

    /** @return \Generator<string, array{MockResponse}> */
    public static function rejectedImages(): \Generator
    {
        yield 'HTML annoncé comme image' => [new MockResponse('<svg onload="alert(1)"></svg>', [
            'response_headers' => ['content-type' => 'image/png'],
        ])];
        yield 'SVG' => [new MockResponse('<svg xmlns="http://www.w3.org/2000/svg"/>')];
        yield 'image tronquée' => [new MockResponse(substr(TestImage::png(), offset: 0, length: 200))];
        yield 'téléchargement au-delà de 5 Mo' => [new MockResponse(
            TestImage::png() . str_repeat("\0", times: 6 * 1024 * 1024),
        )];
        yield 'surface au-delà de 16 Mpx' => [new MockResponse(TestImage::png(4100, 4100))];
        yield 'erreur HTTP' => [new MockResponse('', ['http_code' => 404])];
    }

    public function testIgnoresMissingAddress(): void
    {
        $client = new MockHttpClient();
        $store = new PreviewImageStore($client, new Filesystem(new InMemoryFilesystemAdapter()), new NullLogger());

        self::assertNull($store->storePreview(null));
        self::assertNull($store->storeFavicon(null));
        self::assertSame(0, $client->getRequestsCount());
    }

    private function store(MockResponse $response, Filesystem $storage): PreviewImageStore
    {
        return new PreviewImageStore(new MockHttpClient($response), $storage, new NullLogger());
    }
}
