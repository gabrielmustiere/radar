<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\Type\Team;
use App\Form\Model\UserEdition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserEditionType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $adminLocked = true === ($options['admin_locked'] ?? false);

        $builder->add('email', EmailType::class, [
            'label' => 'Email',
            'attr' => ['data-test' => 'user-email'],
        ])->add('team', EnumType::class, [
            'label' => 'Équipe',
            'class' => Team::class,
            'choice_label' => static fn(Team $team): string => $team->label(),
            'placeholder' => 'Aucune équipe',
            'required' => false,
            'attr' => ['data-test' => 'user-team'],
        ])->add('admin', CheckboxType::class, [
            'label' => 'Admin',
            'required' => false,
            'disabled' => $adminLocked,
            'help' => $adminLocked
                ? 'Ce statut ne peut pas être retiré ici : il s\'agit de votre compte ou de l\'admin permanent.'
                : 'Gère les comptes, l\'équipe des catégories et les sources suivies.',
            'attr' => ['data-test' => 'user-admin'],
        ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserEdition::class,
            'admin_locked' => false,
        ]);
        $resolver->setAllowedTypes('admin_locked', 'bool');
    }
}
