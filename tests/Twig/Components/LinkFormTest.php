<?php

declare(strict_types=1);

namespace App\Tests\Twig\Components;

use PHPUnit\Framework\Attributes\DataProvider;

final class LinkFormTest extends LinkFormTestCase
{
    public function testEnterCreatesTheCategoryWithAnInitialCapitalChecksItAndClearsTheField(): void
    {
        $name = 'mesurer la perf ' . uniqid();
        $component = $this->component();

        $crawler = $component->set('categoryQuery', $name)->call('createCategory')->render()->crawler();

        self::assertTrue($this->pill($crawler, ucfirst($name))->filter('input')->matches(':checked'));
        self::assertSame([$this->category(ucfirst($name))], $this->checkedIds($component));
        self::assertSame('', $this->linkForm($component)->categoryQuery);
    }

    public function testExistingNameInAnotherCaseIsCheckedWithoutCreatingASecondCategory(): void
    {
        $component = $this->component();
        $before = count($this->categories()->findAllOrderedByName());

        $component->set('categoryQuery', '  ARCHITECTURE ')->call('createCategory');

        self::assertSame([$this->category('Architecture')], $this->checkedIds($component));
        self::assertCount($before, $this->categories()->findAllOrderedByName());
    }

    #[DataProvider('invalidNames')]
    public function testInvalidNameIsNotCreatedAndExplained(string $name, string $message): void
    {
        $component = $this->component();
        $before = count($this->categories()->findAllOrderedByName());

        $html = (string) $component->set('categoryQuery', $name)->call('createCategory')->render();

        self::assertStringContainsString($message, $html);
        self::assertCount($before, $this->categories()->findAllOrderedByName());
        self::assertSame([], $this->checkedIds($component));
    }

    /** @return \Generator<string, array{string, string}> */
    public static function invalidNames(): \Generator
    {
        yield 'trop court' => ['a', 'au moins 2 caractères'];
        yield 'trop long' => [str_repeat('x', times: 61), 'au plus 60 caractères'];
    }

    public function testCategoryCreatedBeyondThreeIsKeptButNotChecked(): void
    {
        $name = 'Quatrième ' . uniqid();
        $checked = [$this->category('Symfony'), $this->category('Agents'), $this->category('IA')];
        $component = $this->component()->set('link.categories', $checked);

        $crawler = $component->set('categoryQuery', $name)->call('createCategory')->render()->crawler();

        self::assertEqualsCanonicalizing($checked, $this->checkedIds($component));
        self::assertTrue($this->pill($crawler, $name)->filter('input')->matches(':disabled'));
        self::assertStringContainsString(
            'désélectionnez une catégorie pour la retenir',
            $crawler->filter('[data-test="category-notice"]')->text(),
        );
    }

    public function testExistingNameTypedAtTheCapIsNeitherCreatedNorChecked(): void
    {
        $checked = [$this->category('Symfony'), $this->category('Agents'), $this->category('IA')];
        $component = $this->component()->set('link.categories', $checked);
        $before = count($this->categories()->findAllOrderedByName());

        $crawler = $component->set('categoryQuery', 'architecture')->call('createCategory')->render()->crawler();

        self::assertEqualsCanonicalizing($checked, $this->checkedIds($component));
        self::assertCount($before, $this->categories()->findAllOrderedByName());
        self::assertSame(
            '« Architecture » est disponible : désélectionnez une catégorie pour la retenir.',
            $crawler->filter('[data-test="category-notice"]')->text(),
        );
    }

    public function testFilterHidesNonMatchingPillsButKeepsThemChecked(): void
    {
        $component = $this->component()->set('link.categories', [$this->category('Symfony')]);

        $crawler = $component->set('categoryQuery', 'agen')->render()->crawler();

        self::assertNull($this->pill($crawler, 'Agents')->attr('hidden'));
        $symfony = $this->pill($crawler, 'Symfony');
        self::assertNotNull($symfony->attr('hidden'));
        self::assertTrue($symfony->filter('input')->matches(':checked'));
    }
}
