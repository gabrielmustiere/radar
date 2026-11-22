<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Enum\Type\LinkChannel;
use App\Form\Model\LinkSubmission;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
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
        ])->add('channel', HiddenType::class, [
            'attr' => ['data-test' => 'link-channel'],
        ]);

        // Valeur modifiable par le membre : sans enjeu pour une statistique, une valeur inconnue compte comme un ajout web.
        $builder->get('channel')->addModelTransformer(
            new CallbackTransformer(
                static fn(?LinkChannel $channel): string => ($channel ?? LinkChannel::Web)->value,
                static fn(?string $value): LinkChannel => LinkChannel::tryFrom((string) $value) ?? LinkChannel::Web,
            ),
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LinkSubmission::class,
        ]);
    }
}
