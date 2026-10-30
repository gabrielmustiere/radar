<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AdminEmail;
use App\Exception\AlreadyAdminException;
use App\Form\AdminEmailType;
use App\Repository\AdminEmailRepository;
use App\Security\AdminVoter;
use App\Service\AdminManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(AdminVoter::ADMIN)]
final class AdminController extends AbstractController
{
    #[Route(path: '/administrateurs', name: 'app_admin_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        AdminManager $adminManager,
        AdminEmailRepository $adminEmailRepository,
    ): Response {
        $form = $this->createForm(AdminEmailType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{email: string} $data */
            $data = $form->getData();
            try {
                $adminManager->add($data['email']);
                $this->addFlash('success', 'Admin ajouté.');

                return $this->redirectToRoute('app_admin_index');
            } catch (AlreadyAdminException $exception) {
                $form->get('email')->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('admin/index.html.twig', [
            'form' => $form,
            'permanentEmail' => $adminManager->permanentEmail(),
            'admins' => $adminEmailRepository->findAllOrderedByEmail(),
        ]);
    }

    #[Route(
        path: '/administrateurs/{id}/retirer',
        name: 'app_admin_remove',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid('admin-remove')]
    public function remove(AdminEmail $adminEmail, AdminManager $adminManager): Response
    {
        $adminManager->remove($adminEmail);
        $this->addFlash('success', 'Admin retiré.');

        return $this->redirectToRoute('app_admin_index');
    }
}
