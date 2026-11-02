<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ExtensionKeyManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class ExtensionKeyManagerTest extends TestCase
{
    private MockClock $clock;

    #[\Override]
    protected function setUp(): void
    {
        $this->clock = new MockClock('2026-11-02 10:00:00');
    }

    public function testGeneratedKeyIsReturnedOnceAndOnlyItsHashStored(): void
    {
        $user = new User();

        $key = $this->manager()->generate($user);

        self::assertMatchesRegularExpression('/^rdr_[0-9a-f]{64}$/', $key);
        self::assertSame(hash('sha256', $key), $user->getExtensionKeyHash());
        self::assertEquals($this->clock->now(), $user->getExtensionKeyCreatedAt());
        self::assertNull($user->getExtensionKeyLastUsedAt());
    }

    public function testRegeneratingReplacesTheKeyAndForgetsItsUse(): void
    {
        $user = new User();
        $manager = $this->manager();
        $previous = $manager->generate($user);
        $user->markExtensionKeyUsed($this->clock->now());

        $current = $manager->generate($user);

        self::assertNotSame($previous, $current);
        self::assertSame(hash('sha256', $current), $user->getExtensionKeyHash());
        self::assertNull($user->getExtensionKeyLastUsedAt());
    }

    public function testRevokingForgetsEverything(): void
    {
        $user = new User();
        $manager = $this->manager();
        $manager->generate($user);

        $manager->revoke($user);

        self::assertNull($user->getExtensionKeyHash());
        self::assertNull($user->getExtensionKeyCreatedAt());
        self::assertNull($user->getExtensionKeyLastUsedAt());
    }

    public function testValidKeyAuthenticatesItsOwnerAndRecordsTheUse(): void
    {
        $user = new User();
        $key = $this->manager()->generate($user);
        $this->clock->modify('+1 day');

        self::assertSame($user, $this->manager($user)->authenticate($key));
        self::assertEquals($this->clock->now(), $user->getExtensionKeyLastUsedAt());
    }

    public function testUnknownKeyAuthenticatesNobody(): void
    {
        self::assertNull($this->manager()->authenticate('rdr_inconnue'));
    }

    public function testKeyExpiresAfterNinetyDaysWithoutUse(): void
    {
        $user = new User();
        $key = $this->manager()->generate($user);

        $this->clock->modify('+89 days');
        self::assertSame($user, $this->manager($user)->authenticate($key));

        $this->clock->modify('+91 days');
        self::assertNull($this->manager($user)->authenticate($key));
        self::assertTrue($this->manager()->isExpired($user));
    }

    public function testUserWithoutKeyIsExpired(): void
    {
        self::assertTrue($this->manager()->isExpired(new User()));
    }

    private function manager(?User $found = null): ExtensionKeyManager
    {
        $repository = $this->createStub(UserRepository::class);
        $repository->method('findOneByExtensionKeyHash')->willReturn($found);

        return new ExtensionKeyManager($repository, $this->clock);
    }
}
