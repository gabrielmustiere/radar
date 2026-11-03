<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

/** `isEqualTo()` : `$session` est le compte lu en session, l'argument celui rechargé depuis la base. */
final class UserTest extends TestCase
{
    public function testSameActiveAccountIsEqual(): void
    {
        self::assertTrue($this->user()->isEqualTo($this->user()));
    }

    public function testSessionPasswordFingerprintIsAccepted(): void
    {
        /** @var User $session */
        $session = unserialize(serialize($this->user()));
        self::assertSame(hash('crc32c', data: 'hash-du-mot-de-passe'), $session->getPassword());

        self::assertTrue($session->isEqualTo($this->user()));
    }

    public function testDeactivatedAccountIsNotEqual(): void
    {
        $refreshed = $this->user();
        $refreshed->deactivate();

        self::assertFalse($this->user()->isEqualTo($refreshed));
    }

    public function testChangedPasswordIsNotEqual(): void
    {
        /** @var User $session */
        $session = unserialize(serialize($this->user()));

        self::assertFalse($session->isEqualTo($this->user()->setPassword('nouveau-hash')));
    }

    public function testChangedEmailIsNotEqual(): void
    {
        self::assertFalse($this->user()->isEqualTo($this->user()->setEmail('autre@example.com')));
    }

    public function testOtherKindOfUserIsNotEqual(): void
    {
        self::assertFalse($this->user()->isEqualTo(
            new InMemoryUser('marie@example.com', password: 'hash-du-mot-de-passe'),
        ));
    }

    private function user(): User
    {
        return new User()
            ->setEmail('marie@example.com')
            ->setPassword('hash-du-mot-de-passe');
    }
}
