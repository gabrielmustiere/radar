<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateUserCommandTest extends KernelTestCase
{
    private const string EMAIL = 'nouveau@example.com';

    private UserRepository $users;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $users = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $users);
        $this->users = $users;
        $this->removeUser();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->removeUser();
        parent::tearDown();
    }

    public function testCreatesAMemberWithTheTypedPassword(): void
    {
        $tester = $this->tester();
        $tester->setInputs(['s3cret-Passw0rd']);

        self::assertSame(Command::SUCCESS, $tester->execute(['email' => '  ' . self::EMAIL . ' ']));

        $user = $this->users->findOneByEmail(self::EMAIL);
        self::assertInstanceOf(User::class, $user);
        self::assertSame(['ROLE_USER'], $user->getRoles());
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $hasher);
        self::assertTrue($hasher->isPasswordValid($user, 's3cret-Passw0rd'));
    }

    public function testReadsThePasswordFromStandardInputForDeployment(): void
    {
        $tester = $this->tester();
        $tester->setInputs(['s3cret-Passw0rd']);

        self::assertSame(Command::SUCCESS, $tester->execute(['email' => self::EMAIL, '--password-stdin' => true], [
            'interactive' => false,
        ]));

        $user = $this->users->findOneByEmail(self::EMAIL);
        self::assertInstanceOf(User::class, $user);
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $hasher);
        self::assertTrue($hasher->isPasswordValid($user, 's3cret-Passw0rd'));
    }

    public function testRefusesAnEmailAlreadyUsed(): void
    {
        $tester = $this->tester();
        $tester->setInputs(['s3cret-Passw0rd']);

        self::assertSame(Command::FAILURE, $tester->execute(['email' => 'admin@example.com']));
        self::assertStringContainsString('déjà un compte', $tester->getDisplay());
    }

    public function testRefusesAnInvalidEmail(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::INVALID, $tester->execute(['email' => 'pas-un-email']));
        self::assertNull($this->users->findOneByEmail('pas-un-email'));
    }

    public function testRefusesAnEmptyPassword(): void
    {
        $tester = $this->tester();
        $tester->setInputs(['']);

        self::assertSame(Command::INVALID, $tester->execute(['email' => self::EMAIL]));
        self::assertNull($this->users->findOneByEmail(self::EMAIL));
    }

    private function tester(): CommandTester
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);

        return new CommandTester(new Application($kernel)->find('app:user:create'));
    }

    private function removeUser(): void
    {
        $user = $this->users->findOneByEmail(self::EMAIL);
        if (null === $user) {
            return;
        }

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->remove($user);
        $entityManager->flush();
    }
}
