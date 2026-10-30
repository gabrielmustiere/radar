<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use App\Form\Model\HistoricalLinkSubmission;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class HistoricalLinkType extends AbstractType
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('postedAt', DateType::class, [
            'label' => 'Posté dans Chat le',
            'input' => 'datetime_immutable',
            'required' => false,
            'attr' => ['data-test' => 'link-posted-at'],
        ])->add('poster', EntityType::class, [
            'label' => 'Posté par',
            'class' => User::class,
            'choices' => $this->userRepository->findMembersOrderedByEmail(),
            'choice_label' => 'displayName',
            'placeholder' => 'Choisir un membre',
            'required' => false,
            'attr' => ['data-test' => 'link-poster-select'],
        ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HistoricalLinkSubmission::class,
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return LinkType::class;
    }
}
