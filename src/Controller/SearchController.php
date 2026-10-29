<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    #[Route(path: '/recherche', name: 'app_search', methods: ['GET'])]
    public function index(
        #[MapQueryParameter]
        string $q = '',
        #[MapQueryParameter(options: ['min_range' => 1])]
        ?int $page = null,
    ): Response {
        return $this->render('search/index.html.twig', [
            'q' => $q,
            'page' => $page,
        ]);
    }
}
