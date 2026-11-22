<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Category;
use App\Enum\Type\LinkChannel;
use Symfony\Component\Validator\Constraints as Assert;

class LinkSubmission
{
    public const int MAX_CATEGORIES = 3;

    #[Assert\NotBlank]
    #[Assert\Length(max: 2048)]
    #[Assert\Url(protocols: ['http', 'https'], requireTld: true)]
    public ?string $url = null;

    /** @var list<Category> */
    #[Assert\Count(
        min: 1,
        max: self::MAX_CATEGORIES,
        minMessage: 'Choisissez au moins une catégorie, ou créez-en une.',
        maxMessage: 'Un lien porte au plus trois catégories.',
    )]
    public array $categories = [];

    #[Assert\Length(max: 500)]
    public ?string $context = null;

    /** `null` quand le lien n'est pas ajouté par un membre : reprise de l'historique, article de source. */
    public ?LinkChannel $channel = LinkChannel::Web;
}
