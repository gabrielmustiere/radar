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
    public function index(LinkRepository $linkRepository, #[MapQueryParameter] string $q = ''): Response
    {
        $query = SearchQuery::fromString($q);

        return $this->render('search/index.html.twig', [
            'q' => $q,
            'query' => $query,
            'links' => $linkRepository->search($query),
        ]);
    }
}
