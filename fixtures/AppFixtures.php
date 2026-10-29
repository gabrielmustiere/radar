<?php

declare(strict_types=1);

namespace DataFixtures;

use App\Entity\Category;
use App\Entity\Link;
use App\Entity\User;
use App\Enum\Type\SourceType;
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

        foreach (['marie@example.com', 'karim@example.com'] as $email) {
            $member = new User();
            $member->setEmail($email);
            $member->setPassword($this->passwordHasher->hashPassword($member, 'password'));
            $manager->persist($member);
        }

        $categories = [];
        foreach (['Tester une app mobile', 'Choisir une librairie PHP', 'Concevoir une interface'] as $name) {
            $categories[$name] = new Category($name, new \DateTimeImmutable());
            $manager->persist($categories[$name]);
        }

        $symfony = new Link(
            'https://github.com/symfony/symfony',
            'https://github.com/symfony/symfony',
            [$categories['Choisir une librairie PHP']],
            $user,
            new \DateTimeImmutable('-3 days'),
            SourceType::GitHub,
            'La référence pour tout nouveau projet back.',
        );
        $symfony->applyPreview(
            'symfony/symfony: The Symfony PHP framework',
            'Symfony is a PHP framework for web and console applications and a set of reusable PHP components.',
            null,
            'GitHub',
        );
        $manager->persist($symfony);

        $maestro = new Link(
            'https://x.com/mobile_dev/status/1',
            'https://x.com/mobile_dev/status/1',
            [$categories['Tester une app mobile']],
            $user,
            new \DateTimeImmutable('-40 days'),
            SourceType::X,
        );
        $maestro->markPreviewUnavailable();
        $manager->persist($maestro);

        $manager->flush();
    }
}
