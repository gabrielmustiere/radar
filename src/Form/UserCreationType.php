<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\Type\Team;
use App\Form\Model\UserCreation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserCreationType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'Email',
            'attr' => ['placeholder' => 'prenom@example.com', 'data-test' => 'user-email'],
        ])->add('team', EnumType::class, [
            'label' => 'Équipe',
            'class' => Team::class,
            'choice_label' => static fn(Team $team): string => $team->label(),
            'placeholder' => 'Choisir une équipe',
            'required' => false,
            'attr' => ['data-test' => 'user-team'],
        ])->add('password', TextType::class, [
            'label' => 'Mot de passe provisoire',
            'help' => 'Affiché en clair pour que vous puissiez le transmettre : Radar n\'envoie aucun email. 8 caractères minimum.',
            'attr' => [
                'autocomplete' => 'off',
                'spellcheck' => 'false',
                'class' => 'font-mono',
                'data-test' => 'user-password',
            ],
        ])->add('admin', CheckboxType::class, [
            'label' => 'Admin',
            'help' => 'Gère les comptes, l\'équipe des catégories et les sources suivies.',
            'required' => false,
            'attr' => ['data-test' => 'user-admin'],
        ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserCreation::class,
        ]);
    }
}
