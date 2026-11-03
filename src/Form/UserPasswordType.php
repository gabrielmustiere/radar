<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\UserPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserPasswordType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('password', TextType::class, [
            'label' => 'Nouveau mot de passe provisoire',
            'help' => 'Affiché en clair pour que vous puissiez le transmettre. L\'ancien cesse de fonctionner aussitôt.',
            'attr' => [
                'autocomplete' => 'off',
                'spellcheck' => 'false',
                'class' => 'font-mono',
                'data-test' => 'user-new-password',
            ],
        ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserPassword::class,
        ]);
    }
}
