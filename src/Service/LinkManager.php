<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Entity\Link;
use App\Entity\User;
use App\Form\Model\LinkSubmission;
use App\Message\FetchLinkPreview;
use App\Repository\CategoryRepository;
use App\Service\Preview\SourceTypeResolver;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class LinkManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategoryRepository $categoryRepository,
        private ClockInterface $clock,
        private SourceTypeResolver $sourceTypeResolver,
        private MessageBusInterface $messageBus,
    ) {}

    public function add(LinkSubmission $submission, User $poster): Link
    {
        $categories = $submission->categories;
        $newCategoryName = $submission->newCategoryName();
        if (null !== $newCategoryName) {
            $category = $this->resolveCategory($newCategoryName);
            if (!in_array($category, $categories, strict: true)) {
                $categories[] = $category;
            }
        }

        $context = trim($submission->context ?? '');
        $url = (string) $submission->url;
        $link = new Link(
            $url,
            $categories,
            $poster,
            $this->clock->now(),
            $this->sourceTypeResolver->resolve($url),
            '' === $context ? null : $context,
        );

        $this->entityManager->persist($link);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new FetchLinkPreview((int) $link->getId()));

        return $link;
    }

    private function resolveCategory(string $name): Category
    {
        $key = mb_strtolower($name);
        foreach ($this->categoryRepository->findAllOrderedByName() as $category) {
            if (mb_strtolower($category->getName()) === $key) {
                return $category;
            }
        }

        $category = new Category($name, $this->clock->now());
        $this->entityManager->persist($category);

        return $category;
    }
}
