<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Repository\LinkRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{
    #[Route(path: '/categories/{id}', name: 'app_category_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Category $category, LinkRepository $linkRepository): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
            'links' => $linkRepository->findByCategory($category),
        ]);
    }
}
