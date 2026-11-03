<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Enum\Type\Team;
use App\Repository\CategoryRepository;
use App\Repository\LinkRepository;
use App\Security\AdminVoter;
use App\Service\CategoryManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(AdminVoter::ADMIN)]
#[Route(path: '/admin/categories')]
final class AdminCategoryController extends AbstractController
{
    #[Route(path: '', name: 'app_admin_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository, LinkRepository $linkRepository): Response
    {
        return $this->render('admin_category/index.html.twig', [
            'categories' => $categoryRepository->findAllOrderedByName(),
            'linkCounts' => array_column($linkRepository->countByCategory(), 'linkCount', 'id'),
            'teams' => Team::cases(),
        ]);
    }

    #[Route(path: '/{id}/equipe', name: 'app_admin_category_team', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid('category-team')]
    public function team(Request $request, Category $category, CategoryManager $categoryManager): Response
    {
        $value = $request->getPayload()->getString('team');
        $team = Team::tryFrom($value);
        if ('' !== $value && null === $team) {
            throw new BadRequestHttpException(sprintf('Équipe inconnue : « %s ».', $value));
        }

        $categoryManager->changeTeam($category, $team);
        $this->addFlash('success', sprintf(
            'Catégorie « %s » : %s.',
            $category->getName(),
            null === $team ? 'sans équipe' : 'équipe ' . $team->label(),
        ));

        return $this->redirectToRoute('app_admin_category_index');
    }
}
