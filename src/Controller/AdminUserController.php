<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\EmailAlreadyUsedException;
use App\Exception\ProtectedAccountException;
use App\Form\Model\UserCreation;
use App\Form\Model\UserEdition;
use App\Form\Model\UserPassword;
use App\Form\UserCreationType;
use App\Form\UserEditionType;
use App\Form\UserPasswordType;
use App\Repository\UserRepository;
use App\Security\AdminVoter;
use App\Service\AdminManager;
use App\Service\SystemUserProvider;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(AdminVoter::ADMIN)]
#[Route(path: '/admin/comptes')]
final class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly UserManager $userManager,
        private readonly AdminManager $adminManager,
    ) {}

    #[Route(path: '', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin_user/index.html.twig', [
            'accounts' => array_map(
                /** @return array{user: User, admin: bool, permanent: bool} */
                fn(User $user): array => [
                    'user' => $user,
                    'admin' => $this->adminManager->isAdmin($user),
                    'permanent' => $this->adminManager->isPermanent($user),
                ],
                $userRepository->findMembersOrderedByEmail(),
            ),
        ]);
    }

    #[Route(path: '/nouveau', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $creation = new UserCreation();
        $form = $this->createForm(UserCreationType::class, $creation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $this->userManager->create(
                    (string) $creation->email,
                    (string) $creation->password,
                    $creation->team,
                    $creation->admin,
                );
                $this->addFlash('success', sprintf('Compte créé pour %s.', $user->getUserIdentifier()));

                return $this->redirectToRoute('app_admin_user_index');
            } catch (EmailAlreadyUsedException $exception) {
                $form->get('email')->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('admin_user/new.html.twig', ['form' => $form]);
    }

    #[Route(path: '/{id}', name: 'app_admin_user_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, #[CurrentUser] User $actor): Response
    {
        $this->denyUnlessMember($user);

        $edition = new UserEdition();
        $edition->email = $user->getEmail();
        $edition->team = $user->getTeam();
        // Statut effectif : l'admin permanent l'a même sans le booléen, et une case verrouillée garde cette valeur.
        $edition->admin = $this->adminManager->isAdmin($user);
        $form = $this->createForm(UserEditionType::class, $edition, [
            'admin_locked' =>
                $this->adminManager->isPermanent($user) || $user->getUserIdentifier() === $actor->getUserIdentifier(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->userManager->update($user, $edition, $actor);
                $this->addFlash('success', sprintf('Compte de %s mis à jour.', $user->getUserIdentifier()));

                return $this->redirectToRoute('app_admin_user_index');
            } catch (EmailAlreadyUsedException $exception) {
                $form->get('email')->addError(new FormError($exception->getMessage()));
            } catch (ProtectedAccountException $exception) {
                $form->get('admin')->addError(new FormError($exception->getMessage()));
            }
        }

        $password = new UserPassword();
        $passwordForm = $this->createForm(UserPasswordType::class, $password);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $this->userManager->changePassword($user, (string) $password->password);
            $this->addFlash('success', sprintf('Nouveau mot de passe défini pour %s.', $user->getUserIdentifier()));

            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('admin_user/edit.html.twig', [
            'account' => $user,
            'form' => $form,
            'passwordForm' => $passwordForm,
        ]);
    }

    #[Route(
        path: '/{id}/desactiver',
        name: 'app_admin_user_deactivate',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid('user-deactivate')]
    public function deactivate(User $user, #[CurrentUser] User $actor): Response
    {
        $this->denyUnlessMember($user);

        try {
            $this->userManager->deactivate($user, $actor);
            $this->addFlash('success', sprintf('Compte de %s désactivé.', $user->getUserIdentifier()));
        } catch (ProtectedAccountException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route(path: '/{id}/reactiver', name: 'app_admin_user_activate', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid('user-activate')]
    public function activate(User $user): Response
    {
        $this->denyUnlessMember($user);

        $this->userManager->activate($user);
        $this->addFlash('success', sprintf('Compte de %s réactivé.', $user->getUserIdentifier()));

        return $this->redirectToRoute('app_admin_user_index');
    }

    /** Le compte système ne se gère pas : il publie les articles des sources et ne se connecte jamais. */
    private function denyUnlessMember(User $user): void
    {
        if (SystemUserProvider::EMAIL === $user->getUserIdentifier()) {
            throw $this->createNotFoundException();
        }
    }
}
