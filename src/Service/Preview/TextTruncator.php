<?php

declare(strict_types=1);

namespace App\Service\Preview;

final class TextTruncator
{
    /** Coupe au dernier mot entier qui tient dans `$length` caractères, points de suspension compris. */
    public function truncate(?string $text, int $length): ?string
    {
        if (null === $text || mb_strlen($text) <= $length) {
            return $text;
        }

        $cut = mb_substr($text, start: 0, length: $length - 1);
        $lastSpace = mb_strrpos($cut, needle: ' ');
        if (false !== $lastSpace && $lastSpace > intdiv($length, num2: 2)) {
            $cut = mb_substr($cut, start: 0, length: $lastSpace);
        }

        return (string) preg_replace('/[\s,;:.\-–—]+$/u', replacement: '', subject: $cut) . '…';
    }
}
