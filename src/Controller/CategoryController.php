<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Enum\Type\FeedType;
use App\Enum\Type\Team;
use App\Repository\LinkRepository;
use App\Search\LinkFilter;
use App\Search\SearchQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{
    public function __construct(
        private readonly LinkRepository $linkRepository,
    ) {}

    #[Route(path: '/categories/{id}', name: 'app_category_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        Category $category,
        #[MapQueryParameter]
        string $q = '',
        #[MapQueryParameter(options: ['min_range' => 1])]
        int $page = 1,
        #[MapQueryParameter]
        ?Team $team = null,
        #[MapQueryParameter]
        ?FeedType $type = null,
    ): Response {
        $links = $this->linkRepository->findFiltered(
            new LinkFilter(SearchQuery::fromString($q), $team, $category, $type),
            $page,
        );
        if ($links->getPageNumber() > 1 && 0 === $links->count()) {
            throw $this->createNotFoundException();
        }

        return $this->render('category/show.html.twig', [
            'category' => $category,
            'links' => $links,
            'q' => $q,
            'page' => $page,
            'team' => $team,
            'type' => $type,
        ]);
    }
}
