<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview;

use App\Entity\Link;
use App\Entity\User;
use App\Enum\Type\SourceType;
use App\Service\Preview\LinkBannerStore;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class LinkBannerStoreTest extends TestCase
{
    private Filesystem $storage;

    #[\Override]
    protected function setUp(): void
    {
        $this->storage = new Filesystem(new InMemoryFilesystemAdapter());
    }

    public function testNameDependsOnTheLinkAndTheSecret(): void
    {
        $store = $this->store();
        $other = new LinkBannerStore($this->storage, new NullLogger(), 'autre');

        self::assertSame($store->name($this->link(1)), $store->name($this->link(1)));
        self::assertNotSame($store->name($this->link(1)), $store->name($this->link(2)));
        self::assertNotSame($store->name($this->link(1)), $other->name($this->link(1)));
    }

    public function testRemovesBannerAndIgnoresAMissingOne(): void
    {
        $store = $this->store();
        $link = $this->link(1);
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

    private function link(int $id): Link
    {
        $link = new Link(
            'https://example.com/article',
            'https://example.com/article',
            [],
            new User()->setEmail('marie@example.com'),
            new \DateTimeImmutable(),
            SourceType::Article,
        );
        new \ReflectionProperty(Link::class, 'id')->setValue($link, $id);

        return $link;
    }
}
