<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\ApiResource\ExtensionLink;
use App\Entity\Category;
use App\Entity\User;
use App\Form\Model\LinkSubmission;
use App\Repository\CategoryRepository;
use App\Service\LinkManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @implements ProcessorInterface<ExtensionLink, ExtensionLink> */
final readonly class ExtensionLinkProcessor implements ProcessorInterface
{
    public function __construct(
        private LinkManager $linkManager,
        private CategoryRepository $categoryRepository,
        private ValidatorInterface $validator,
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): ExtensionLink {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Le pare-feu de l\'API authentifie toujours un membre.');
        }

        $submission = new LinkSubmission();
        $submission->url = $data->url;
        $submission->context = $data->context;
        $submission->categories = $this->categoryRepository->findByIdsOrderedByName($data->categories);

        $violations = $this->validator->validate($submission);
        if (count($submission->categories) !== count(array_unique($data->categories))) {
            $violations->add(
                new ConstraintViolation(
                    'Une catégorie choisie n\'existe plus : rechargez la liste.',
                    null,
                    [],
                    $submission,
                    'categories',
                    $data->categories,
                ),
            );
        }

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $link = $this->linkManager->add($submission, $user);

        return new ExtensionLink(
            id: $link->getId(),
            url: $link->getUrl(),
            context: $link->getContext(),
            categories: array_map(
                static fn(Category $category): int => (int) $category->getId(),
                $submission->categories,
            ),
            cardUrl: $this->urlGenerator->generate(
                'app_link_show',
                ['id' => $link->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
        );
    }
}
