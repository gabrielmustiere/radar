<?php

declare(strict_types=1);

namespace App\Tests\E2E;

/**
 * Vrais liens réservés aux E2E (relevés le 29/10/2026), absents des fixtures : chaque ajout en consomme un
 * jusqu'au prochain `make db-reset`. Les vidéos YouTube passent en dernier : hors connexion, YouTube peut
 * rediriger vers sa page de consentement à l'ouverture.
 */
final class RealLinks
{
    public const array URLS = [
        'https://github.com/api-platform/core',
        'https://github.com/php/frankenphp',
        'https://github.com/ollama/ollama',
        'https://github.com/anthropics/anthropic-sdk-php',
        'https://github.com/hesreallyhim/awesome-claude-code',
        'https://github.com/anthropics/claude-code-action',
        'https://github.com/CodelyTV/php-ddd-example',
        'https://github.com/Sairyss/domain-driven-hexagon',
        'https://symfony.com/blog/new-in-symfony-8-2-security-expressions-everywhere',
        'https://simonwillison.net/2025/Jun/16/the-lethal-trifecta/',
        'https://simonwillison.net/2025/Oct/16/claude-skills/',
        'https://martinfowler.com/bliki/StranglerFigApplication.html',
        'https://x.com/symfony/status/2115798561954648231',
        'https://x.com/karpathy/status/2105819303471976479',
        'https://x.com/bcherny/status/2111158313463525518',
        'https://x.com/simonbrown/status/2096160353574949298',
        'https://www.youtube.com/watch?v=qjSAMOkrdIQ',
        'https://www.youtube.com/watch?v=D7_ipDqhtwk',
        'https://www.youtube.com/watch?v=6eBSHbLKuN0',
        'https://www.youtube.com/watch?v=Hi5aDfRe-aE',
    ];
}
