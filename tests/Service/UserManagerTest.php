<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Enum\Type\Team;
use App\Exception\EmailAlreadyUsedException;
use App\Exception\ProtectedAccountException;
use App\Form\Model\UserEdition;
use App\Repository\UserRepository;
use App\Service\AdminManager;
use App\Service\UserManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserManagerTest extends TestCase
{
    private const string PERMANENT = 'admin@example.com';

    public function testCreatedAccountHasALowercaseEmailAHashedPasswordAndItsTeam(): void
    {
        $user = $this->manager()->create('Marie.Curie@Example.com', 's3cret-Passw0rd', Team::Qa, admin: true);

        self::assertSame('marie.curie@example.com', $user->getEmail());
        self::assertSame('hashed:s3cret-Passw0rd', $user->getPassword());
        self::assertSame(Team::Qa, $user->getTeam());
        self::assertTrue($user->isAdmin());
        self::assertTrue($user->isActive());
    }

    public function testCreationRefusesAnEmailAlreadyUsedWhateverItsCase(): void
    {
        $this->expectException(EmailAlreadyUsedException::class);

        $this->manager(owner: new User())->create('MARIE@example.com', 's3cret-Passw0rd', null, admin: false);
    }

    public function testEditionRefusesTheEmailOfAnotherAccountButNotItsOwn(): void
    {
        $user = $this->user('marie@example.com');
        $this->manager(owner: $user)->update($user, self::edition('MARIE@example.com', Team::Product), $user);
        self::assertSame('marie@example.com', $user->getEmail());
        self::assertSame(Team::Product, $user->getTeam());

        $this->expectException(EmailAlreadyUsedException::class);
        $this->manager(owner: new User())->update($user, self::edition('karim@example.com'), $user);
    }

    /** @param \Closure(UserManager): void $action */
    #[DataProvider('protectedActions')]
    public function testProtectedAccountIsRefused(\Closure $action, string $message): void
    {
        $this->expectExceptionObject(new ProtectedAccountException($message));

        $action($this->manager());
    }

    /** @return \Generator<string, array{\Closure(UserManager): void, string}> */
    public static function protectedActions(): \Generator
    {
        $actor = new User()
            ->setEmail('chef@example.com')
            ->setAdmin(true);
        $permanent = new User()->setEmail(self::PERMANENT);

        yield 'se retirer le statut admin' => [
            static function (UserManager $manager) use ($actor): void {
                $manager->update($actor, self::edition('chef@example.com'), $actor);
            },
            'Vous ne pouvez pas vous retirer le statut admin.',
        ];
        yield 'retirer le statut à l\'admin permanent' => [
            static function (UserManager $manager) use ($actor, $permanent): void {
                $manager->update($permanent, self::edition(self::PERMANENT), $actor);
            },
            'L\'admin permanent garde toujours le statut admin.',
        ];
        yield 'se désactiver' => [
            static function (UserManager $manager) use ($actor): void {
                $manager->deactivate($actor, $actor);
            },
            'Vous ne pouvez pas désactiver votre propre compte.',
        ];
        yield 'désactiver l\'admin permanent' => [
            static function (UserManager $manager) use ($actor, $permanent): void {
                $manager->deactivate($permanent, $actor);
            },
            'L\'admin permanent ne peut pas être désactivé.',
        ];
    }

    public function testDeactivatedAccountCanBeReactivated(): void
    {
        $user = $this->user('marie@example.com');
        $manager = $this->manager();

        $manager->deactivate($user, $this->user('chef@example.com'));
        self::assertFalse($user->isActive());

        $manager->activate($user);
        self::assertTrue($user->isActive());
    }

    public function testNewPasswordIsHashed(): void
    {
        $user = $this->user('marie@example.com');

        $this->manager()->changePassword($user, 'nouveau-mot-de-passe');

        self::assertSame('hashed:nouveau-mot-de-passe', $user->getPassword());
    }

    /** @param ?User $owner compte que renvoie la recherche d'email, quel que soit l'email cherché */
    private function manager(?User $owner = null): UserManager
    {
        $repository = $this->createStub(UserRepository::class);
        $repository->method('findOneByEmailIgnoringCase')->willReturn($owner);
        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher
            ->method('hashPassword')
            ->willReturnCallback(static fn(User $_user, string $plain): string => 'hashed:' . $plain);

        return new UserManager($repository, $hasher, new AdminManager(self::PERMANENT));
    }

    /** @param non-empty-string $email */
    private function user(string $email): User
    {
        return new User()->setEmail($email);
    }

    private static function edition(string $email, ?Team $team = null, bool $admin = false): UserEdition
    {
        $edition = new UserEdition();
        $edition->email = $email;
        $edition->team = $team;
        $edition->admin = $admin;

        return $edition;
    }
}
