<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\LinkRepository;
use App\Search\SearchQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    #[Route(path: '/recherche', name: 'app_search', methods: ['GET'])]
    public function index(
        LinkRepository $linkRepository,
        #[MapQueryParameter]
        string $q = '',
        #[MapQueryParameter(options: ['min_range' => 1])]
        int $page = 1,
    ): Response {
        $query = SearchQuery::fromString($q);
        $links = $linkRepository->search($query, $page);
        if ($links->getPageNumber() > 1 && 0 === $links->count()) {
            throw $this->createNotFoundException();
        }

        return $this->render('search/index.html.twig', [
            'q' => $q,
            'query' => $query,
            'links' => $links,
        ]);
    }
}
