<?php

declare(strict_types=1);

namespace App\Tests\Twig\Components;

use App\Entity\Link;
use App\Entity\User;
use App\Enum\Type\SourceType;
use App\Repository\LinkRepository;
use App\Repository\UserRepository;
use App\Twig\Components\PendingLinkCard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

final class PendingLinkCardTest extends WebTestCase
{
    use InteractsWithLiveComponents;

    public function testPollsWhilePreviewIsPendingThenStops(): void
    {
        $client = self::createClient();
        $link = $this->pendingLink();
        $component = $this->createLiveComponent('PendingLinkCard', ['link' => $link], $client)->actingAs($this->user());

        self::assertStringContainsString('data-poll="delay(3000)|refresh"', (string) $component->render());

        $link->applyPreview('Titre enrichi', null, null, null);
        $this->entityManager()->flush();
        $html = (string) $component->call('refresh')->render();

        self::assertStringContainsString('Titre enrichi', $html);
        self::assertStringNotContainsString('data-poll', $html);
    }

    public function testStopsPollingAfterTheCapWhenNoWorkerAnswers(): void
    {
        $client = self::createClient();
        $component = $this->createLiveComponent(
            'PendingLinkCard',
            [
                'link' => $this->pendingLink(),
                'refreshes' => PendingLinkCard::MAX_REFRESHES - 1,
            ],
            $client,
        )->actingAs($this->user());

        self::assertStringNotContainsString('data-poll', (string) $component->call('refresh')->render());
    }

    private function pendingLink(): Link
    {
        $url = 'https://example.com/pending-' . uniqid();
        $link = new Link($url, $url, [], $this->user(), new \DateTimeImmutable(), SourceType::Article);
        $repository = self::getContainer()->get(LinkRepository::class);
        self::assertInstanceOf(LinkRepository::class, $repository);
        $repository->save($link);

        return $link;
    }

    private function user(): User
    {
        $repository = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $repository);
        $user = $repository->findOneBy(['email' => 'admin@example.com']);
        self::assertNotNull($user);

        return $user;
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
