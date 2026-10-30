<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class AdminEmailType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'Email',
            'constraints' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 180)],
            'attr' => ['placeholder' => 'prenom@example.com', 'data-test' => 'admin-email'],
        ]);
    }
}
