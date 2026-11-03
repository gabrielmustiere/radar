<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class UserCheckerTest extends TestCase
{
    public function testDeactivatedAccountIsRefusedWithAMessage(): void
    {
        $user = new User();
        $user->deactivate();

        $this->expectExceptionObject(new CustomUserMessageAccountStatusException('Ce compte est désactivé.'));

        new UserChecker()->checkPreAuth($user);
    }

    public function testActiveAccountIsAccepted(): void
    {
        $this->expectNotToPerformAssertions();

        new UserChecker()->checkPreAuth(new User());
    }
}
