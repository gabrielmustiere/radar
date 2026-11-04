<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ExtensionPackage;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ExtensionDownloadTest extends WebTestCase
{
    public function testPageGuidesAMemberWithoutAKey(): void
    {
        $this->memberClient()->request('GET', '/extension');

        $this->assertInstallationGuideIsShown();
    }

    public function testPageGuidesAMemberWhoAlreadyHasAKey(): void
    {
        $client = $this->memberClient();
        $crawler = $client->request('GET', '/extension');
        $client->submit($crawler->filter('[data-test="extension-key-generate"]')->form());

        self::assertSelectorExists('[data-test="extension-key-value"]');
        $this->assertInstallationGuideIsShown();

        $client->request('GET', '/extension');

        $this->assertInstallationGuideIsShown();
    }

    public function testKeyGenerationBringsTheMemberBackToTheKeyCard(): void
    {
        $this->memberClient()->request('GET', '/extension');

        self::assertSelectorExists('section#cle [data-test="extension-key-generate"]');
        self::assertSelectorExists('form[action="/extension/generer#cle"]');
    }

    public function testFaqCoversTheThreeThemesAndPointsToChat(): void
    {
        $this->memberClient()->request('GET', '/extension');

        foreach (['Installation', 'Mises à jour', 'Clé personnelle'] as $theme) {
            self::assertAnySelectorTextContains('[data-test="extension-faq"] h3', $theme);
        }
        self::assertSelectorTextContains('[data-test="extension-faq-help"]', 'espace Google Chat de la veille');
    }

    public function testMemberDownloadsThePackageOfTheDisplayedVersion(): void
    {
        $client = $this->memberClient();
        $crawler = $client->request('GET', '/extension');
        $link = $crawler->filter('[data-test="extension-download-link"]');

        $client->request('GET', (string) $link->attr('href'));

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/zip');
        self::assertResponseHeaderSame('Content-Disposition', 'attachment; filename=radar-extension.zip');
        self::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        self::assertTrue($client->getResponse()->headers->hasCacheControlDirective('private'));

        $path = (string) tempnam(sys_get_temp_dir(), prefix: 'radar-download-test');
        file_put_contents($path, (string) $client->getResponse()->getContent());
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($path));
        /** @var array{version: string} $manifest */
        $manifest = json_decode(
            (string) $zip->getFromName('manifest.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
        $zip->close();
        unlink($path);

        self::assertSame($crawler->filter('[data-test="extension-version"]')->text(), $manifest['version']);
    }

    public function testAnonymousVisitorCannotDownload(): void
    {
        $client = self::createClient();

        $client->request('GET', '/extension/telecharger');

        self::assertResponseRedirects('http://localhost/login');
    }

    private function assertInstallationGuideIsShown(): void
    {
        $package = self::getContainer()->get(ExtensionPackage::class);
        self::assertInstanceOf(ExtensionPackage::class, $package);

        self::assertSelectorTextSame('[data-test="extension-version"]', $package->version());
        self::assertSelectorExists('[data-test="extension-download-link"][download]');
        self::assertSelectorTextContains('[data-test="extension-install"]', 'Mode développeur');
        self::assertSelectorExists('[data-test="extension-faq"]');
    }

    private function memberClient(): KernelBrowser
    {
        $client = self::createClient();
        $users = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $users);
        $member = new User()
            ->setEmail(uniqid('download-') . '@example.com')
            ->setPassword('!');
        $users->save($member);
        $client->loginUser($member);

        return $client;
    }
}
