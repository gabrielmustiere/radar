<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Link;
use App\Entity\Source;
use App\Entity\User;
use App\Enum\Type\SourceType;
use App\Service\LinkAnnouncementCard;
use App\Service\Preview\LinkPreview;
use App\Twig\LinkExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class LinkAnnouncementCardTestCase extends TestCase
{
    /** @return array<array-key, mixed> */
    protected function card(Link $link): array
    {
        $options = $this->message($link)->getOptions();
        self::assertNotNull($options);
        /** @var array{cardsV2: array{0: array{card: array<array-key, mixed>}}} $payload */
        $payload = $options->toArray();

        return $payload['cardsV2'][0]['card'];
    }

    /** @return list<array{textParagraph?: array{text: string}, buttonList?: array{buttons: list<mixed>}}> */
    protected function sections(Link $link): array
    {
        /** @var list<array{textParagraph?: array{text: string}, buttonList?: array{buttons: list<mixed>}}> */
        return $this->card($link)['sections'][0]['widgets'] ?? [];
    }

    protected function widgets(Link $link): string
    {
        return $this->json($this->sections($link));
    }

    protected function json(mixed $value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function message(Link $link): ChatMessage
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnCallback(static fn(string $route): string => 'https://radar.example/' . $route);

        return new LinkAnnouncementCard($urlGenerator, new LinkExtension())->message($link);
    }

    protected function source(string $name): Source
    {
        return new Source('https://example.com/feed', $name, [], new \DateTimeImmutable());
    }

    protected function linkAt(string $url, SourceType $type): Link
    {
        return new Link($url, $url, [], new User()->setEmail('marie@example.com'), new \DateTimeImmutable(), $type);
    }

    protected function link(
        SourceType $type = SourceType::Article,
        ?string $context = null,
        ?string $title = null,
        ?string $description = null,
        ?Source $source = null,
    ): Link {
        $link = new Link(
            'https://maestro.dev',
            'https://maestro.dev',
            [
                new Category('Tester une app mobile', new \DateTimeImmutable()),
                new Category('Outils QA', new \DateTimeImmutable()),
            ],
            new User()->setEmail('marie@example.com'),
            new \DateTimeImmutable(),
            $type,
            $context,
            source: $source,
        );
        $link->applyPreview(
            new LinkPreview(title: $title, description: $description),
            null,
            null,
            new \DateTimeImmutable(),
        );

        return $link;
    }
}
