<?php

declare(strict_types=1);

namespace App\Enum\Type;

/**
 * Type d'un lien dans le filtre du fil : sa plateforme, sauf s'il vient d'une source suivie (qui prime, comme sur la carte).
 */
enum FeedType: string
{
    case Video = 'youtube';
    case Repository = 'github';
    case Post = 'x';
    case Followed = 'rss';
    case Article = 'article';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Vidéos',
            self::Repository => 'Dépôts GitHub',
            self::Post => 'Posts X',
            self::Followed => 'Sources suivies',
            self::Article => 'Articles',
        };
    }

    /** Plateforme correspondante, `null` pour une source suivie (toutes plateformes). */
    public function sourceType(): ?SourceType
    {
        return match ($this) {
            self::Video => SourceType::YouTube,
            self::Repository => SourceType::GitHub,
            self::Post => SourceType::X,
            self::Followed => null,
            self::Article => SourceType::Article,
        };
    }

    /** Type d'un lien publié par un membre (hors source suivie) ; une image n'en a pas. */
    public static function fromSourceType(SourceType $sourceType): ?self
    {
        foreach (self::cases() as $type) {
            if ($type->sourceType() === $sourceType) {
                return $type;
            }
        }

        return null;
    }
}
