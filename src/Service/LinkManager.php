<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Link;
use App\Entity\User;
use App\Exception\DuplicateLinkException;
use App\Form\Model\LinkSubmission;
use App\Message\FetchLinkPreview;
use App\Repository\LinkRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class LinkManager
{
    public function __construct(
        private LinkRepository $linkRepository,
        private LinkUrlAnalyzer $urlAnalyzer,
        private CategoryResolver $categoryResolver,
        private ClockInterface $clock,
        private MessageBusInterface $messageBus,
    ) {}

    /** @throws DuplicateLinkException quand le lien existe déjà : rien n'est créé */
    public function add(LinkSubmission $submission, User $poster): Link
    {
        $url = (string) $submission->url;
        $normalizedUrl = $this->urlAnalyzer->normalize($url);
        $existing = $this->linkRepository->findOneByNormalizedUrl($normalizedUrl);
        if (null !== $existing) {
            throw new DuplicateLinkException($existing);
        }

        $categories = $submission->categories;
        $newCategoryName = $submission->newCategoryName();
        if (null !== $newCategoryName) {
            $category = $this->categoryResolver->resolve($newCategoryName);
            if (!in_array($category, $categories, strict: true)) {
                $categories[] = $category;
            }
        }

        $context = trim($submission->context ?? '');
        $link = new Link(
            $url,
            $normalizedUrl,
            $categories,
            $poster,
            $this->clock->now(),
            $this->urlAnalyzer->sourceType($url),
            '' === $context ? null : $context,
        );

        $this->linkRepository->save($link);
        $this->messageBus->dispatch(new FetchLinkPreview((int) $link->getId()));

        return $link;
    }
}
