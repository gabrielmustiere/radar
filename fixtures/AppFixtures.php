<?php

declare(strict_types=1);

namespace DataFixtures;

use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);

        $manager->persist($user);

        foreach (['Tester une app mobile', 'Choisir une librairie PHP', 'Concevoir une interface'] as $name) {
            $manager->persist(new Category($name, new \DateTimeImmutable()));
        }

        $manager->flush();
    }
}
