<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Form\Model\SourceSubmission;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SourceType extends AbstractType
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('feedUrl', UrlType::class, [
            'label' => 'Adresse du flux RSS ou Atom',
            'default_protocol' => null,
            'attr' => ['placeholder' => 'https://…/feed', 'data-test' => 'source-url'],
        ])->add('categories', EntityType::class, [
            'label' => 'Catégories (une ou deux)',
            'class' => Category::class,
            'choices' => $this->categoryRepository->findAllOrderedByName(),
            'choice_label' => 'name',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
        ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SourceSubmission::class,
        ]);
    }
}
