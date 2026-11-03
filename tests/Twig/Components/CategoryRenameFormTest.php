<?php

declare(strict_types=1);

namespace App\Tests\Twig\Components;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Service\CategoryResolver;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

final class CategoryRenameFormTest extends WebTestCase
{
    use InteractsWithLiveComponents;

    public function testAdminRenamesTheCategoryAndReturnsToItsPage(): void
    {
        $client = self::createClient();
        $category = $this->category('Ancien nom ' . uniqid());
        $name = 'nouveau nom ' . uniqid();
        $component = $this->createLiveComponent(
            'CategoryRenameForm',
            ['category' => $category],
            $client,
        )->actingAs($this->user('admin@example.com'));

        $response = $component->set('name', $name)->call('save')->response();

        self::assertSame(302, $response->getStatusCode());
        self::assertSame(sprintf('/categories/%d', $category->getId()), $response->headers->get('Location'));
        $repository = self::getContainer()->get(CategoryRepository::class);
        self::assertInstanceOf(CategoryRepository::class, $repository);
        self::assertSame(ucfirst($name), $repository->find((int) $category->getId())?->getName());
    }

    public function testNameAlreadyTakenIsRefusedInTheModal(): void
    {
        $client = self::createClient();
        $taken = $this->category('Déjà prise ' . uniqid());
        $component = $this->createLiveComponent(
            'CategoryRenameForm',
            ['category' => $this->category('Autre ' . uniqid())],
            $client,
        )->actingAs($this->user('admin@example.com'));

        $html = (string) $component->set('name', mb_strtolower($taken->getName()))->call('save')->render();

        self::assertStringContainsString('Une catégorie porte déjà ce nom.', $html);
    }

    public function testTooShortNameIsRefused(): void
    {
        $client = self::createClient();
        $component = $this->createLiveComponent(
            'CategoryRenameForm',
            ['category' => $this->category('Court ' . uniqid())],
            $client,
        )->actingAs($this->user('admin@example.com'));

        $html = (string) $component->set('name', ' a ')->call('save')->render();

        self::assertStringContainsString('au moins 2 caractères', $html);
    }

    public function testMemberWhoIsNotAdminCannotRename(): void
    {
        $client = self::createClient();
        $component = $this->createLiveComponent(
            'CategoryRenameForm',
            ['category' => $this->category('Protégée ' . uniqid())],
            $client,
        )->actingAs($this->user('marie@example.com'));

        $this->expectException(AccessDeniedException::class);

        $component->set('name', 'Interdit ' . uniqid())->call('save');
    }

    private function category(string $name): Category
    {
        $resolver = self::getContainer()->get(CategoryResolver::class);
        self::assertInstanceOf(CategoryResolver::class, $resolver);

        return $resolver->resolve($name, null);
    }

    private function user(string $email): User
    {
        $repository = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $repository);
        $user = $repository->findOneBy(['email' => $email]);
        self::assertNotNull($user);

        return $user;
    }
}
