<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\LinkRepository;
use App\Search\SearchQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class PageController extends AbstractController
{
    #[Route(path: '/', name: 'app_page')]
    public function index(
        LinkRepository $linkRepository,
        #[MapQueryParameter]
        string $q = '',
        #[MapQueryParameter(options: ['min_range' => 1])]
        int $page = 1,
    ): Response {
        $links = $linkRepository->findLatest(SearchQuery::fromString($q), $page);
        if ($links->getPageNumber() > 1 && 0 === $links->count()) {
            throw $this->createNotFoundException();
        }

        return $this->render('page/index.html.twig', [
            'links' => $links,
            'q' => $q,
            'page' => $page,
        ]);
    }

    #[Route(path: '/design-system', name: 'app_design_system')]
    public function designSystem(): Response
    {
        return $this->render('page/design-system.html.twig');
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route(path: '/test-email', name: 'app_test_email', env: 'dev')]
    public function testEmail(MailerInterface $mailer): Response
    {
        $email = new Email()
            ->from('hello@example.com')
            ->to('test@example.com')
            ->subject('Test Email from Symfony')
            ->text('This is a test email sent from Symfony and Mailpit.')
            ->html('<p>This is a test email sent from <b>Symfony</b> and <b>Mailpit</b>.</p>');

        $mailer->send($email);

        $this->addFlash('success', 'Email de test envoyé avec succès !');

        return $this->redirectToRoute('app_login');
    }
}
