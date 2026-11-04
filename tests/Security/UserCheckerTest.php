<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class UserCheckerTest extends TestCase
{
    public function testDeactivatedAccountIsRefusedWithAMessageOnceAuthenticated(): void
    {
        $user = new User();
        $user->deactivate();

        $this->expectExceptionObject(new CustomUserMessageAccountStatusException('Ce compte est désactivé.'));

        new UserChecker()->checkPostAuth($user);
    }

    public function testDeactivatedAccountIsNotRefusedBeforeThePasswordCheck(): void
    {
        $user = new User();
        $user->deactivate();
        $this->expectNotToPerformAssertions();

        new UserChecker()->checkPreAuth($user);
    }

    public function testActiveAccountIsAccepted(): void
    {
        $this->expectNotToPerformAssertions();

        $checker = new UserChecker();
        $checker->checkPreAuth(new User());
        $checker->checkPostAuth(new User());
    }
}
