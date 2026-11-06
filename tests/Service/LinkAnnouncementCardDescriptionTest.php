<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Enum\Type\SourceType;
use PHPUnit\Framework\Attributes\TestWith;

final class LinkAnnouncementCardDescriptionTest extends LinkAnnouncementCardTestCase
{
    #[TestWith([SourceType::GitHub])]
    #[TestWith([SourceType::Article])]
    public function testShowsTheDescriptionOfARepositoryOrAnArticleBeforeTheReason(SourceType $type): void
    {
        $link = $this->link($type, 'Pourquoi.', 'Maestro', 'Tests mobiles.');

        self::assertStringContainsString(
            '[{"textParagraph":{"text":"Tests mobiles."}},{"textParagraph":{"text":"<b>',
            $this->widgets($link),
        );
    }

    #[TestWith([SourceType::YouTube])]
    #[TestWith([SourceType::X])]
    #[TestWith([SourceType::Image])]
    public function testHidesTheDescriptionOfOtherTypes(SourceType $type): void
    {
        self::assertStringNotContainsString(
            'Tests mobiles.',
            $this->widgets($this->link($type, null, 'Maestro', 'Tests mobiles.')),
        );
    }

    #[TestWith(['Maestro'])]
    #[TestWith(['  MAESTRO '])]
    #[TestWith(['M a e s t r o'])]
    public function testHidesADescriptionThatRepeatsTheTitle(string $description): void
    {
        self::assertSame(1, substr_count(
            $this->widgets($this->link(title: 'Maestro', description: $description)),
            needle: '"textParagraph"',
        ));
    }

    public function testKeepsADescriptionThatExtendsTheTitle(): void
    {
        self::assertStringContainsString(
            '"Maestro : tester une app mobile"',
            $this->widgets($this->link(title: 'Maestro', description: 'Maestro : tester une app mobile')),
        );
    }

    public function testCutsALongDescriptionWithoutBreakingACharacter(): void
    {
        $sections = $this->sections($this->link(title: 'Maestro', description: str_repeat('é', times: 200)));

        $text = $sections[0]['textParagraph']['text'] ?? '';
        self::assertSame(160, mb_strlen($text));
        self::assertTrue(mb_check_encoding($text, encoding: 'UTF-8'));
        self::assertStringEndsWith('é…', $text);
    }

    public function testKeepsADescriptionOfExactlyTheLimit(): void
    {
        $description = str_repeat('a', times: 160);

        self::assertStringContainsString(
            sprintf('"%s"', $description),
            $this->widgets($this->link(title: 'Maestro', description: $description)),
        );
    }
}
