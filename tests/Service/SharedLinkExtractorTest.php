<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\SharedLinkExtractor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SharedLinkExtractorTest extends TestCase
{
    /** @param array{?string, ?string, ?string} $shared `url`, `text`, `title` reçus du menu Partager */
    #[DataProvider('sharedContents')]
    public function testFindsTheSharedLink(array $shared, ?string $expected): void
    {
        self::assertSame($expected, new SharedLinkExtractor()->extract(...$shared));
    }

    /** @return \Generator<string, array{array{?string, ?string, ?string}, ?string}> */
    public static function sharedContents(): \Generator
    {
        yield 'lien fourni à part' => [['https://example.com/a', 'Un titre', null], 'https://example.com/a'];
        yield 'lien glissé dans le texte' => [
            [null, 'Vidéo du jour https://youtu.be/abc', null],
            'https://youtu.be/abc',
        ];
        yield 'premier de plusieurs liens' => [
            [null, 'https://example.com/a puis https://example.com/b', null],
            'https://example.com/a',
        ];
        yield 'ponctuation finale retirée' => [
            [null, 'À lire : https://example.com/a.', null],
            'https://example.com/a',
        ];
        yield 'guillemet français retiré' => [[null, '« https://example.com/a »', null], 'https://example.com/a'];
        yield 'parenthèse ouvrante hors du lien' => [
            [null, '(voir https://example.com/a)', null],
            'https://example.com/a',
        ];
        yield 'parenthèse appartenant au lien' => [
            [null, 'https://en.wikipedia.org/wiki/Radar_(homonymie)', null],
            'https://en.wikipedia.org/wiki/Radar_(homonymie)',
        ];
        yield 'repli sur le titre' => [[null, 'Rien ici', 'Lien https://example.com/t'], 'https://example.com/t'];
        yield 'url invalide, lien dans le texte' => [
            ['pas un lien', 'https://example.com/a', null],
            'https://example.com/a',
        ];
        yield 'schéma non web ignoré' => [['ftp://example.com/a', 'javascript:alert(1)', null], null];
        yield 'aucun lien' => [[null, 'Une citation sans lien', 'Titre'], null];
        yield 'rien de partagé' => [[null, null, null], null];
    }
}
