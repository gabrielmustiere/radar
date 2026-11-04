<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Link;
use App\Exception\LinkNotReadyException;
use App\Security\AdminVoter;
use App\Service\LinkManager;
use App\Twig\LinkExtension;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(AdminVoter::ADMIN)]
#[Route(path: '/admin/liens')]
final class AdminLinkController extends AbstractController
{
    public function __construct(
        private readonly LinkExtension $linkExtension,
    ) {}

    #[Route(path: '/{id}', name: 'app_admin_link_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Link $link): Response
    {
        return $this->render('admin_link/show.html.twig', ['link' => $link]);
    }

    #[Route(
        path: '/{id}/relancer',
        name: 'app_admin_link_reannounce',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid('link-reannounce')]
    public function reannounce(Link $link, LinkManager $linkManager): Response
    {
        try {
            $linkManager->reannounce($link);
            $this->addFlash('success', sprintf('Annonce de « %s » mise en file.', $this->linkExtension->title($link)));
        } catch (LinkNotReadyException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_page');
    }

    #[Route(path: '/{id}/supprimer', name: 'app_admin_link_remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid('link-remove')]
    public function remove(Link $link, LinkManager $linkManager): Response
    {
        $linkManager->remove($link);
        $this->addFlash('success', sprintf('Lien « %s » supprimé.', $this->linkExtension->title($link)));

        return $this->redirectToRoute('app_page');
    }
}
