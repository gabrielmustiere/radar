<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Enum\Type\LinkChannel;

/** Partage depuis le menu Partager d'Android : la cible du manifeste ouvre le formulaire d'ajout prérempli. */
final class LinkShareTest extends LinkWebTestCase
{
    public function testLinkSharedInTheTextIsPrefilled(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/liens/ajouter', [
            'title' => 'Vidéo',
            'text' => 'Vidéo du jour https://example.com/partage.',
        ]);

        self::assertResponseIsSuccessful();
        self::assertInputValueSame('link[url]', 'https://example.com/partage');
        self::assertInputValueSame('link[channel]', LinkChannel::Share->value);
        self::assertSelectorNotExists('[data-test="share-no-link"]');
    }

    public function testLinkSharedAsUrlIsPrefilled(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/liens/ajouter', ['url' => 'https://example.com/direct']);

        self::assertInputValueSame('link[url]', 'https://example.com/direct');
    }

    public function testShareWithoutLinkWarnsAndLeavesTheAddressEmpty(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/liens/ajouter', ['text' => 'Une citation sans lien']);

        self::assertSelectorTextContains('[data-test="share-no-link"]', 'Aucun lien trouvé dans le partage');
        self::assertInputValueSame('link[url]', '');
        self::assertInputValueSame('link[channel]', LinkChannel::Share->value);
    }

    public function testOrdinaryFormIsNeitherPrefilledNorAShare(): void
    {
        $client = $this->loggedClient();

        $client->request('GET', '/liens/ajouter');

        self::assertInputValueSame('link[channel]', LinkChannel::Web->value);
        self::assertSelectorNotExists('[data-test="share-no-link"]');
    }

    public function testSharedLinkAlreadyInRadarShowsTheDuplicate(): void
    {
        $client = $this->loggedClient();
        $url = 'https://example.com/' . uniqid('partage-doublon-');
        $this->submit($client, ['url' => $url, 'categories' => [$this->categoryId('Symfony')]]);

        $client->request('GET', '/liens/ajouter', ['text' => 'Déjà vu ' . $url]);

        self::assertSelectorExists('[data-test="link-duplicate"]');
    }

    public function testAnonymousShareLandsOnThePrefilledFormAfterLogin(): void
    {
        $client = self::createClient();

        $client->request('GET', '/liens/ajouter', ['text' => 'https://example.com/apres-connexion']);
        self::assertResponseRedirects('/login');
        $client->followRedirect();
        $client->submitForm('Se connecter', ['_username' => 'admin@example.com', '_password' => 'password']);

        self::assertResponseRedirects(
            'http://localhost/liens/ajouter?text=https%3A%2F%2Fexample.com%2Fapres-connexion',
        );
        $client->followRedirect();
        self::assertInputValueSame('link[url]', 'https://example.com/apres-connexion');
    }

    public function testSubmittedShareIsRecordedAsShare(): void
    {
        $client = $this->loggedClient();
        $url = 'https://example.com/' . uniqid('canal-partage-');

        $this->submit($client, [
            'url' => $url,
            'categories' => [$this->categoryId('Symfony')],
            'channel' => LinkChannel::Share->value,
        ]);

        self::assertResponseRedirects('/');
        self::assertSame(LinkChannel::Share, $this->linkByUrl($url)->getChannel());
    }

    public function testUnknownChannelIsRecordedAsWeb(): void
    {
        $client = $this->loggedClient();
        $url = 'https://example.com/' . uniqid('canal-inconnu-');

        $this->submit($client, ['url' => $url, 'categories' => [$this->categoryId('Symfony')], 'channel' => 'pigeon']);

        self::assertResponseRedirects('/');
        self::assertSame(LinkChannel::Web, $this->linkByUrl($url)->getChannel());
    }
}
