<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\ExtensionKeyManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

final class ExtensionKeyController extends AbstractController
{
    #[Route(path: '/extension', name: 'app_extension_key', methods: ['GET'])]
    public function index(#[CurrentUser] User $user, ExtensionKeyManager $extensionKeyManager): Response
    {
        return $this->renderPage($user, $extensionKeyManager, key: null);
    }

    /** La clé en clair n'existe que dans cette réponse : ni redirection, ni flash, rien en session. */
    #[Route(path: '/extension/generer', name: 'app_extension_key_generate', methods: ['POST'])]
    #[IsCsrfTokenValid('extension-key')]
    public function generate(#[CurrentUser] User $user, ExtensionKeyManager $extensionKeyManager): Response
    {
        $response = $this->renderPage($user, $extensionKeyManager, $extensionKeyManager->generate($user));
        // Sans ce marqueur, le listener de session remplace `no-store` par `private, must-revalidate`.
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    #[Route(path: '/extension/revoquer', name: 'app_extension_key_revoke', methods: ['POST'])]
    #[IsCsrfTokenValid('extension-key')]
    public function revoke(#[CurrentUser] User $user, ExtensionKeyManager $extensionKeyManager): Response
    {
        $extensionKeyManager->revoke($user);
        $this->addFlash('success', 'Clé révoquée : l\'extension ne peut plus ajouter de lien.');

        return $this->redirectToRoute('app_extension_key');
    }

    private function renderPage(User $user, ExtensionKeyManager $extensionKeyManager, ?string $key): Response
    {
        return $this->render('extension_key/index.html.twig', [
            'user' => $user,
            'key' => $key,
            'expired' => null !== $user->getExtensionKeyHash() && $extensionKeyManager->isExpired($user),
        ]);
    }
}
