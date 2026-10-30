<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Link;
use App\Repository\LinkOpeningRepository;
use App\Service\Preview\LinkPreview;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class LinkPlayTest extends EnrichedLinkWebTestCase
{
    public function testPlayingVideoInItsCardCountsAsAnOpening(): void
    {
        $client = $this->loggedClient();
        $link = $this->video();
        $token = $this->playToken($client, $link);

        $client->request('POST', sprintf('/liens/%d/lecture', $link->getId()), ['_token' => $token]);

        self::assertResponseStatusCodeSame(204);
        self::assertSame(1, $this->openingCount($link));
    }

    public function testRefusesPlayWithoutValidToken(): void
    {
        $client = $this->loggedClient();
        $link = $this->video();

        $client->request('POST', sprintf('/liens/%d/lecture', $link->getId()), ['_token' => 'invalide']);

        // Un jeton CSRF invalide est une erreur d'authentification : le firewall renvoie vers la connexion.
        self::assertResponseRedirects('/login');
        self::assertSame(0, $this->openingCount($link));
    }

    private function video(): Link
    {
        return $this->enrichedLink(
            'https://www.youtube.com/watch?v=qjSAMOkrdIQ&u=' . uniqid(),
            new LinkPreview(title: '20 Years of Symfony'),
        );
    }

    private function playToken(KernelBrowser $client, Link $link): string
    {
        $crawler = $client->request('GET', sprintf('/liens/%d', $link->getId()));

        return (string) $crawler->filter('[data-test="link-video"]')->attr('data-video-facade-token-value');
    }

    private function openingCount(Link $link): int
    {
        $repository = self::getContainer()->get(LinkOpeningRepository::class);
        self::assertInstanceOf(LinkOpeningRepository::class, $repository);

        return $repository->count(['link' => $link->getId()]);
    }
}
