<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Link;
use App\Entity\User;
use App\Exception\DuplicateLinkException;
use App\Form\HistoricalLinkType;
use App\Form\LinkType;
use App\Form\Model\HistoricalLinkSubmission;
use App\Form\Model\LinkSubmission;
use App\Service\LinkManager;
use App\Service\LinkOpeningRecorder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class LinkController extends AbstractController
{
    #[Route(path: '/liens/ajouter', name: 'app_link_new', methods: ['GET', 'POST'])]
    public function new(Request $request, LinkManager $linkManager, #[CurrentUser] User $user): Response
    {
        $submission = new LinkSubmission();
        $form = $this->createForm(LinkType::class, $submission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $linkManager->add($submission, $user);
                $this->addFlash('success', 'Lien ajouté.');

                return $this->redirectToRoute('app_page');
            } catch (DuplicateLinkException $exception) {
                // La carte existante est affichée par le composant LinkForm.
                $form->get('url')->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('link/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/liens/reprendre', name: 'app_link_seed', methods: ['GET', 'POST'])]
    public function seed(Request $request, LinkManager $linkManager, #[CurrentUser] User $user): Response
    {
        $submission = new HistoricalLinkSubmission();
        $submission->poster = $user;
        $form = $this->createForm(HistoricalLinkType::class, $submission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $linkManager->seed($submission);
                $this->addFlash('success', 'Lien repris de Chat.');

                return $this->redirectToRoute('app_page');
            } catch (DuplicateLinkException $exception) {
                // La carte existante est affichée par le composant LinkForm.
                $form->get('url')->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('link/seed.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/liens/{id}', name: 'app_link_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Link $link): Response
    {
        return $this->render('link/show.html.twig', [
            'link' => $link,
        ]);
    }

    #[Route(path: '/liens/{id}/ouvrir', name: 'app_link_open', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function open(Link $link, LinkOpeningRecorder $recorder, #[CurrentUser] User $user): Response
    {
        $recorder->record($link, $user);

        return $this->redirect($link->getUrl());
    }
}
