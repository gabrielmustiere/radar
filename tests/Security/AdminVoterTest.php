<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\AdminVoter;
use App\Service\AdminManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class AdminVoterTest extends TestCase
{
    public function testPermanentAdminIsGrantedWhateverTheCase(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote(
            'Admin@Example.com',
            permanent: 'admin@example.com',
            admin: false,
        ));
    }

    public function testAccountWithAdminStatusIsGranted(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote('marie@example.com', permanent: '', admin: true));
    }

    public function testOtherMemberIsDenied(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote(
            'karim@example.com',
            permanent: 'admin@example.com',
            admin: false,
        ));
    }

    public function testEmptyPermanentAdminGrantsNobody(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote('karim@example.com', permanent: '', admin: false));
    }

    public function testAnonymousIsDenied(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter('admin@example.com')->vote(
            new NullToken(),
            null,
            [AdminVoter::ADMIN],
        ));
    }

    public function testAbstainsOnOtherAttributes(): void
    {
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter('admin@example.com')->vote(
            new NullToken(),
            null,
            ['ROLE_USER'],
        ));
    }

    /** @param non-empty-string $email */
    private function vote(string $email, string $permanent, bool $admin): int
    {
        $user = new User()
            ->setEmail($email)
            ->setAdmin($admin);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        return $this->voter($permanent)->vote($token, null, [AdminVoter::ADMIN]);
    }

    private function voter(string $permanent): AdminVoter
    {
        return new AdminVoter(new AdminManager($permanent));
    }
}
