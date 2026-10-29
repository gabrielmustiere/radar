<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Repository\LinkRepository;
use App\Search\SearchQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{
    #[Route(path: '/categories/{id}', name: 'app_category_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        Category $category,
        LinkRepository $linkRepository,
        #[MapQueryParameter]
        string $q = '',
        #[MapQueryParameter(options: ['min_range' => 1])]
        int $page = 1,
    ): Response {
        $links = $linkRepository->findByCategory($category, SearchQuery::fromString($q), $page);
        if ($links->getPageNumber() > 1 && 0 === $links->count()) {
            throw $this->createNotFoundException();
        }

        return $this->render('category/show.html.twig', [
            'category' => $category,
            'links' => $links,
            'q' => $q,
            'page' => $page,
        ]);
    }
}
