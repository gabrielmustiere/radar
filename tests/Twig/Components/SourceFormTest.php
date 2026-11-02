<?php

declare(strict_types=1);

namespace App\Tests\Twig\Components;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Twig\Components\SourceForm;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

final class SourceFormTest extends WebTestCase
{
    use InteractsWithLiveComponents;

    public function testAdminCreatesACategoryWithEnterAndItIsChecked(): void
    {
        $client = self::createClient();
        $name = 'suivre les notes de version ' . uniqid();
        $component = $this->createLiveComponent('SourceForm', [], $client)->actingAs($this->user('admin@example.com'));

        $html = (string) $component->set('categoryQuery', $name)->call('createCategory')->render();

        $form = $component->component();
        self::assertInstanceOf(SourceForm::class, $form);
        /** @var array{categories?: list<int|string>} $values */
        $values = $form->formValues;
        self::assertCount(1, $values['categories'] ?? []);
        self::assertStringContainsString(ucfirst($name), $html);
    }

    public function testMemberWhoIsNotAdminCannotCallTheActions(): void
    {
        $client = self::createClient();
        $component = $this->createLiveComponent('SourceForm', [], $client)->actingAs($this->user('marie@example.com'));

        $this->expectException(AccessDeniedException::class);

        $component->set('categoryQuery', 'Interdit ' . uniqid())->call('createCategory');
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
