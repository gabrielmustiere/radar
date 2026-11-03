<?php

declare(strict_types=1);

namespace App\Search;

use App\Entity\Category;
use App\Enum\Type\FeedType;
use App\Enum\Type\Team;

/**
 * Filtres du fil en entonnoir : recherche, équipe, catégorie et type, cumulés (ET).
 * Un lien appartient à une équipe si l'une de ses catégories a cette équipe.
 */
final readonly class LinkFilter
{
    public function __construct(
        public SearchQuery $query,
        public ?Team $team = null,
        public ?Category $category = null,
        public ?FeedType $type = null,
    ) {}

    public function withoutTeam(): self
    {
        return new self($this->query, null, $this->category, $this->type);
    }

    public function withoutCategory(): self
    {
        return new self($this->query, $this->team, null, $this->type);
    }

    public function withoutType(): self
    {
        return new self($this->query, $this->team, $this->category, null);
    }
}
