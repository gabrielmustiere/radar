<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Form\Model\LinkSubmission;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
        $builder->add('url', UrlType::class, [
            'label' => 'Adresse du lien',
            'default_protocol' => null,
            'attr' => ['placeholder' => 'https://…', 'autofocus' => true, 'data-test' => 'link-url'],
        ])->add('categories', EntityType::class, [
            'label' => 'Catégories (une ou deux)',
            'class' => Category::class,
            'choices' => $this->categoryRepository->findAllOrderedByName(),
            'choice_label' => 'name',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
        ])->add('newCategory', TextType::class, [
            'label' => 'Ou créer une catégorie',
            'required' => false,
            'help' => 'Un besoin plutôt qu\'une rubrique : « tester une app mobile » plutôt que « Mobile ».',
            'attr' => ['placeholder' => 'Nouvelle catégorie-besoin', 'data-test' => 'link-new-category'],
        ])->add('context', TextareaType::class, [
            'label' => 'Pourquoi c\'est utile (facultatif)',
            'required' => false,
            'attr' => ['rows' => 3, 'maxlength' => 500, 'data-test' => 'link-context'],
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
