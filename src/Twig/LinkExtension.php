<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;

final class LinkExtension
{
    #[AsTwigFilter('link_host')]
    public function host(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return str_starts_with($host, 'www.') ? substr($host, offset: 4) : $host;
    }
}
