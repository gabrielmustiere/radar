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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class LinkAnnouncementCardTest extends TestCase
{
    private const string BUTTONS =
        '{"buttonList":{"buttons":['
            . '{"text":"Ouvrir le lien","onClick":{"openLink":{"url":"https://maestro.dev"}}},'
            . '{"text":"Voir dans Radar","onClick":{"openLink":{"url":"https://radar.example/app_link_show"}}}]}}';

    public function testFullCardCarriesBannerContextPosterAndButtons(): void
    {
        $link = $this->link(context: 'Pour la recette mobile.', title: 'Maestro', description: 'Tests mobiles.');

        $card = $this->card($link, 'abc');

        self::assertSame(
            [
                'title' => 'Maestro',
                'subtitle' => 'Article · Tester une app mobile, Outils QA',
                'imageUrl' => 'https://radar.example/app_pagelogo.png',
                'imageType' => 'SQUARE',
            ],
            $card['header'] ?? null,
        );
        self::assertSame('[{"image":{"imageUrl":"https://radar.example/app_announcement_banner","altText":"Maestro"}},'
        . '{"textParagraph":{"text":"Pour la recette mobile."}},'
        . '{"textParagraph":{"text":"Posté par marie"}},'
        . self::BUTTONS
        . ']', $this->widgets($link, 'abc'));

        $message = $this->message($link, 'abc');
        self::assertSame(
            'Maestro · Article · Tester une app mobile, Outils QA · Posté par marie',
            $message->getSubject(),
        );
        self::assertSame($message->getSubject(), $message->getOptions()?->toArray()['fallbackText'] ?? null);
    }

    public function testCompactCardFallsBackOnWhatTheLinkHas(): void
    {
        $bare = $this->link();

        self::assertSame('[{"textParagraph":{"text":"Posté par marie"}},' . self::BUTTONS . ']', $this->widgets(
            $bare,
            null,
        ));
        self::assertSame('https://maestro.dev', $this->card($bare, null)['header']['title'] ?? null);
        self::assertStringContainsString('{"textParagraph":{"text":"Tests mobiles."}}', $this->widgets(
            $this->link(description: 'Tests mobiles.'),
            null,
        ));
    }

    public function testNamesTheFollowedSourceInsteadOfThePoster(): void
    {
        $link = $this->link(
            source: new Source('https://symfony.com/feed', 'Symfony Blog', [], new \DateTimeImmutable()),
        );

        $widgets = $this->widgets($link, null);

        self::assertStringContainsString('"Via Symfony Blog"', $widgets);
        self::assertStringNotContainsString('Posté par', $widgets);
    }

    public function testNoUntrustedFragmentKeepsItsMarkup(): void
    {
        $hostile = '<users/all><a href="https://piege.example">clic</a>';
        $source = new Source('https://example.com/feed', $hostile, [], new \DateTimeImmutable());
        $link = new Link(
            'https://example.com/article',
            'https://example.com/article',
            [new Category($hostile, new \DateTimeImmutable())],
            new User()->setEmail($hostile . '@example.com'),
            new \DateTimeImmutable(),
            SourceType::Article,
            $hostile,
            source: $source,
        );
        $link->applyPreview(
            new LinkPreview(title: $hostile, description: $hostile),
            null,
            null,
            new \DateTimeImmutable(),
        );

        $message = $this->message($link, 'abc');

        self::assertStringNotContainsString('<', $this->json($message->getOptions()?->toArray()));
        self::assertStringNotContainsString('<', $message->getSubject());
        self::assertStringContainsString('‹users/all›', $this->widgets($link, null));

        $withoutContext = new Link(
            'https://example.com/article',
            'https://example.com/article',
            [],
            new User()->setEmail('<users/all>@example.com'),
            new \DateTimeImmutable(),
            SourceType::Article,
        );
        $withoutContext->applyPreview(
            new LinkPreview(description: '<users/all>'),
            null,
            null,
            new \DateTimeImmutable(),
        );
        self::assertStringNotContainsString('<', $this->widgets($withoutContext, null));
    }

    public function testEncodesAnUrlThatWouldBreakOutOfItsMarkup(): void
    {
        $url = 'https://example.com/a|b>c<d';
        $link = new Link(
            $url,
            $url,
            [new Category('Symfony', new \DateTimeImmutable())],
            new User()->setEmail('marie@example.com'),
            new \DateTimeImmutable(),
            SourceType::Article,
        );

        self::assertSame('https://example.com/a%7Cb%3Ec%3Cd', $this->card($link, null)['header']['title'] ?? null);
        self::assertStringContainsString('"url":"https://example.com/a%7Cb%3Ec%3Cd"', $this->widgets($link, null));
    }

    /** @return array<array-key, mixed> */
    private function card(Link $link, ?string $bannerName): array
    {
        $options = $this->message($link, $bannerName)->getOptions();
        self::assertNotNull($options);
        /** @var array{cardsV2: array{0: array{card: array<array-key, mixed>}}} $payload */
        $payload = $options->toArray();

        return $payload['cardsV2'][0]['card'];
    }

    private function widgets(Link $link, ?string $bannerName): string
    {
        return $this->json($this->card($link, $bannerName)['sections'][0]['widgets'] ?? null);
    }

    private function json(mixed $value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function message(Link $link, ?string $bannerName): ChatMessage
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnCallback(static fn(string $route): string => 'https://radar.example/' . $route);

        return new LinkAnnouncementCard($urlGenerator)->message($link, $bannerName);
    }

    private function link(
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
            SourceType::Article,
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
