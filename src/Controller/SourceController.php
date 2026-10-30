<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Source;
use App\Enum\Type\FetchWindow;
use App\Exception\AlreadyFollowedSourceException;
use App\Exception\InvalidFeedException;
use App\Form\Model\SourceSubmission;
use App\Form\SourceType;
use App\Repository\SourceRepository;
use App\Security\AdminVoter;
use App\Service\SourceManager;
use App\Service\SourcePublisher;
use App\Service\SystemUserProvider;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(AdminVoter::ADMIN)]
final class SourceController extends AbstractController
{
    #[Route(path: '/sources', name: 'app_source_index', methods: ['GET', 'POST'])]
    public function index(Request $request, SourceManager $sourceManager, SourceRepository $sourceRepository): Response
    {
        $submission = new SourceSubmission();
        $form = $this->createForm(SourceType::class, $submission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $source = $sourceManager->register($submission);
                $this->addFlash('success', sprintf('Source « %s » suivie.', $source->getName()));

                return $this->redirectToRoute('app_source_index');
            } catch (InvalidFeedException|AlreadyFollowedSourceException $exception) {
                $form->get('feedUrl')->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('source/index.html.twig', [
            'form' => $form,
            'sources' => $sourceRepository->findActiveOrderedByName(),
            'windows' => FetchWindow::cases(),
        ]);
    }

    /**
     * La relève horaire, lancée à la demande : les nouveaux articles sont publiés et annoncés comme d'habitude, et
     * ceux parus dans la fenêtre choisie sont repris même déjà vus (ceux présents à l'ajout de la source, par exemple).
     */
    #[Route(path: '/sources/{id}/relever', name: 'app_source_fetch', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid('source-fetch')]
    public function fetch(
        Request $request,
        Source $source,
        SourcePublisher $publisher,
        SystemUserProvider $systemUserProvider,
        ClockInterface $clock,
    ): Response {
        if (!$source->isActive()) {
            throw $this->createNotFoundException();
        }

        $window = FetchWindow::tryFrom($request->getPayload()->getString('since')) ?? FetchWindow::OneHour;
        $published = $publisher->fetch($source, $systemUserProvider->radar(), $window->since($clock->now()));
        if (null === $published) {
            $this->addFlash('error', sprintf(
                'Relève de « %s » impossible : %s',
                $source->getName(),
                $source->getLastError(),
            ));

            return $this->redirectToRoute('app_source_index');
        }

        $this->addFlash('success', sprintf(
            'Relève de « %s » sur %s : %s.',
            $source->getName(),
            $window->label(),
            match ($published) {
                0 => 'aucun nouvel article',
                1 => '1 article publié',
                default => sprintf('%d articles publiés', $published),
            },
        ));

        return $this->redirectToRoute('app_source_index');
    }

    #[Route(path: '/sources/{id}/retirer', name: 'app_source_remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid('source-remove')]
    public function remove(Source $source, SourceManager $sourceManager): Response
    {
        $sourceManager->remove($source);
        $this->addFlash('success', sprintf('Source « %s » retirée.', $source->getName()));

        return $this->redirectToRoute('app_source_index');
    }
}
