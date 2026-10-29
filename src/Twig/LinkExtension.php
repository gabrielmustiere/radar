<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Link;
use Psr\Clock\ClockInterface;
use Twig\Attribute\AsTwigFilter;

final readonly class LinkExtension
{
    public function __construct(
        private ClockInterface $clock,
    ) {}

    #[AsTwigFilter('link_host')]
    public function host(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return str_starts_with($host, 'www.') ? substr($host, offset: 4) : $host;
    }

    /**
     * Liens du fil regroupés par jour d'origine (heure de Paris), dans leur ordre.
     *
     * @param list<Link> $links
     *
     * @return array<string, non-empty-list<Link>> clé `Y-m-d`
     */
    #[AsTwigFilter('by_day')]
    public function byDay(array $links): array
    {
        $days = [];
        foreach ($links as $link) {
            $days[$link
                ->getPostedAt()
                ->setTimezone(new \DateTimeZone('Europe/Paris'))
                ->format('Y-m-d')][] = $link;
        }

        return $days;
    }

    /** Intitulé d'un jour du fil, à l'heure de Paris (R11) : « Aujourd'hui », « Hier », « lundi 26 octobre ». */
    #[AsTwigFilter('day_label')]
    public function dayLabel(\DateTimeImmutable $date): string
    {
        $timezone = new \DateTimeZone('Europe/Paris');
        $day = $date->setTimezone($timezone)->format('Y-m-d');
        $today = $this->clock->now()->setTimezone($timezone);

        if ($day === $today->format('Y-m-d')) {
            return 'Aujourd\'hui';
        }

        if ($day === $today->modify('-1 day')->format('Y-m-d')) {
            return 'Hier';
        }

        $pattern = $date->format('Y') === $today->format('Y') ? 'EEEE d MMMM' : 'EEEE d MMMM y';
        $label = (string) \IntlDateFormatter::create('fr_FR', pattern: $pattern, timezone: $timezone)?->format($date);

        return mb_ucfirst($label);
    }
}
