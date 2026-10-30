<?php

declare(strict_types=1);

namespace App\Tests\Service\Preview\Extractor;

use App\Service\Preview\Extractor\GitHubExtractor;
use App\Service\Preview\JsonFetcher;
use App\Tests\Service\Preview\PreviewFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GitHubExtractorTest extends TestCase
{
    public function testReadsRepositoryFromApiWithToken(): void
    {
        $response = new MockResponse(PreviewFixture::content('github-api-repo.json'));

        $preview = $this->extractor(new MockHttpClient($response), 'secret')->extract(
            'https://github.com/symfony/symfony.git',
            null,
        );

        self::assertSame('https://api.github.com/repos/symfony/symfony', $response->getRequestUrl());
        self::assertStringContainsString(
            'Authorization: Bearer secret',
            (string) json_encode($response->getRequestOptions()),
        );
        self::assertSame('symfony/symfony', $preview->title);
        self::assertSame('The Symfony PHP framework', $preview->description);
        self::assertSame(30_812, $preview->githubStars);
        self::assertSame('PHP', $preview->githubLanguage);
    }

    public function testLeavesTitleOfIssueToItsPageButKeepsRepositoryFigures(): void
    {
        $response = new MockResponse(PreviewFixture::content('github-api-repo.json'));

        $preview = $this->extractor(new MockHttpClient($response), 'secret')->extract(
            'https://github.com/symfony/symfony/issues/42',
            null,
        );

        self::assertSame('https://api.github.com/repos/symfony/symfony', $response->getRequestUrl());
        self::assertNull($preview->title);
        self::assertNull($preview->description);
        self::assertSame(30_812, $preview->githubStars);
    }

    public function testLeavesRepositoryToOpenGraphWithoutToken(): void
    {
        $client = new MockHttpClient();

        self::assertTrue($this->extractor($client, '')->extract('https://github.com/symfony/symfony', null)->isEmpty());
        self::assertSame(0, $client->getRequestsCount());
    }

    #[DataProvider('notRepositories')]
    public function testIgnoresPagesThatAreNotRepositories(string $url): void
    {
        $client = new MockHttpClient();

        self::assertTrue($this->extractor($client, 'secret')->extract($url, null)->isEmpty());
        self::assertSame(0, $client->getRequestsCount());
    }

    /** @return \Generator<string, array{string}> */
    public static function notRepositories(): \Generator
    {
        yield 'profil' => ['https://github.com/symfony'];
        yield 'page réservée' => ['https://github.com/topics/php'];
        yield 'autre domaine' => ['https://gitlab.com/symfony/symfony'];
        yield 'segment parent' => ['https://github.com/../user'];
    }

    private function extractor(MockHttpClient $client, #[\SensitiveParameter] string $token): GitHubExtractor
    {
        return new GitHubExtractor(new JsonFetcher($client, new NullLogger()), $token);
    }
}
