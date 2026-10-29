<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Mots exploitables d'une saisie de recherche : chacun doit se retrouver dans le lien (ET).
 * La casse est conservée : `LIKE` (SQLite) ignore la casse ASCII mais pas celle des lettres accentuées.
 */
final readonly class SearchQuery
{
    private const int MIN_WORD_LENGTH = 2;
    private const int MAX_WORDS = 5;

    /** @param list<non-empty-string> $words */
    private function __construct(
        public array $words,
    ) {}

    public static function fromString(string $input): self
    {
        $parts = preg_split('/\s+/u', trim($input), flags: PREG_SPLIT_NO_EMPTY);
        $words = [];
        foreach (false === $parts ? [] : $parts as $word) {
            $key = mb_strtolower($word);
            if ('' !== $word && mb_strlen($word) >= self::MIN_WORD_LENGTH && !array_key_exists($key, $words)) {
                $words[$key] = $word;
            }
        }

        return new self(array_slice(array_values($words), offset: 0, length: self::MAX_WORDS));
    }

    public function isEmpty(): bool
    {
        return [] === $this->words;
    }
}
