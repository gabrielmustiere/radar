<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Entity\Link;
use App\Entity\User;
use App\Enum\Type\SourceType;
use App\Service\Preview\LinkBannerStore;
use App\Service\Preview\LinkPreview;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class LinkBannerStoreTest extends TestCase
{
    private const string PREVIEW = 'previews/apercu.webp';

    private Filesystem $storage;

    #[\Override]
    protected function setUp(): void
    {
        $this->storage = new Filesystem(new InMemoryFilesystemAdapter());
    }

    public function testStoresBannerFromTheStoredPreviewAsJpeg(): void
    {
        $this->storage->write(self::PREVIEW, TestImage::png(600, 314));

        $name = (string) $this->store()->store($this->link(1, self::PREVIEW));

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $name);
        $size = getimagesizefromstring($this->storage->read(LinkBannerStore::path($name)));
        self::assertSame([600, 200, 'image/jpeg'], [$size[0] ?? null, $size[1] ?? null, $size['mime'] ?? null]);
    }

    public function testFlattensTransparencyOnWhite(): void
    {
        $transparent = imagecreatetruecolor(width: 600, height: 314);
        imagesavealpha($transparent, enable: true);
        imagealphablending($transparent, enable: false);
        $color = (int) imagecolorallocatealpha($transparent, red: 0, green: 0, blue: 0, alpha: 127);
        imagefill($transparent, x: 0, y: 0, color: $color);
        ob_start();
        imagewebp($transparent);
        $this->storage->write(self::PREVIEW, (string) ob_get_clean());

        $name = (string) $this->store()->store($this->link(1, self::PREVIEW));

        $banner = imagecreatefromstring($this->storage->read(LinkBannerStore::path($name)));
        self::assertNotFalse($banner);
        self::assertGreaterThan(240, ((int) imagecolorat($banner, x: 300, y: 100) >> 16) & 0xFF);
    }

    public function testHasNoBannerWithoutStoredPreview(): void
    {
        self::assertNull($this->store()->store($this->link(1, null)));
        self::assertSame([], $this->storage->listContents('/', deep: true)->toArray());
    }

    public function testHasNoBannerWhenThePreviewFileIsGone(): void
    {
        self::assertNull($this->store()->store($this->link(1, self::PREVIEW)));
    }

    #[TestWith([''])]
    #[TestWith(['octets corrompus'])]
    public function testHasNoBannerWhenThePreviewIsUnreadable(string $bytes): void
    {
        $this->storage->write(self::PREVIEW, $bytes);

        self::assertNull($this->store()->store($this->link(1, self::PREVIEW)));
    }

    public function testNameDependsOnTheLinkAndTheSecret(): void
    {
        $store = $this->store();
        $other = new LinkBannerStore($this->storage, new NullLogger(), 'autre');

        self::assertSame($store->name($this->link(1, null)), $store->name($this->link(1, null)));
        self::assertNotSame($store->name($this->link(1, null)), $store->name($this->link(2, null)));
        self::assertNotSame($store->name($this->link(1, null)), $other->name($this->link(1, null)));
    }

    public function testRemovesBannerAndIgnoresAMissingOne(): void
    {
        $store = $this->store();
        $link = $this->link(1, null);
        $path = LinkBannerStore::path($store->name($link));
        $this->storage->write($path, 'JPEG');

        $store->remove($link);
        $store->remove($link);

        self::assertFalse($this->storage->fileExists($path));
    }

    private function store(): LinkBannerStore
    {
        return new LinkBannerStore($this->storage, new NullLogger(), 'secret');
    }

    private function link(int $id, ?string $imagePath): Link
    {
        $link = new Link(
            'https://example.com/article',
            'https://example.com/article',
            [],
            new User()->setEmail('marie@example.com'),
            new \DateTimeImmutable(),
            SourceType::Article,
        );
        $link->applyPreview(new LinkPreview(), $imagePath, null, new \DateTimeImmutable());
        new \ReflectionProperty(Link::class, 'id')->setValue($link, $id);

        return $link;
    }
}
