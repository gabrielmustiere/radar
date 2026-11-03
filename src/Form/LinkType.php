<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Form\Model\LinkSubmission;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class LinkType extends AbstractType
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('context', TextareaType::class, [
            'label' => 'Pourquoi c\'est utile (facultatif)',
            'required' => false,
            'attr' => ['rows' => 3, 'maxlength' => 500, 'data-test' => 'link-context'],
        ])->add('categories', EntityType::class, [
            'label' => 'Catégories (une à trois)',
            'class' => Category::class,
            'choices' => $this->categoryRepository->findAllOrderedByName(),
            'choice_label' => 'name',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
        ])->add('url', UrlType::class, [
            'label' => 'Adresse du lien',
            'default_protocol' => null,
            'attr' => ['placeholder' => 'https://…', 'autofocus' => true, 'data-test' => 'link-url'],
        ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LinkSubmission::class,
        ]);
    }
}
