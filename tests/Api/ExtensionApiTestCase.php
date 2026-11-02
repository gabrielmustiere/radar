<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Service\ExtensionKeyManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Appels de l'extension : JSON-LD, clé en `Authorization: Bearer`. Les compteurs du limiteur persistent d'une
 * exécution à l'autre : chaque appel part d'une IP propre au test, sauf IP imposée.
 */
abstract class ExtensionApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    private string $ip;

    #[\Override]
    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->ip = self::randomIp('10');
    }

    protected static function randomIp(string $network): string
    {
        return sprintf(
            '%s.%d.%d.%d',
            $network,
            random_int(min: 0, max: 255),
            random_int(min: 0, max: 255),
            random_int(min: 1, max: 254),
        );
    }

    protected function member(): User
    {
        $user = new User()
            ->setEmail(uniqid('extension-') . '@example.com')
            ->setPassword('!');
        $this->users()->save($user);

        return $user;
    }

    protected function keyFor(User $user): string
    {
        $manager = self::getContainer()->get(ExtensionKeyManager::class);
        self::assertInstanceOf(ExtensionKeyManager::class, $manager);

        return $manager->generate($user);
    }

    /**
     * @param array<string, mixed>|null $json
     *
     * @return array<string, mixed>
     */
    protected function api(string $method, string $uri, ?string $key, ?array $json = null, ?string $ip = null): array
    {
        $server = ['HTTP_ACCEPT' => 'application/ld+json', 'REMOTE_ADDR' => $ip ?? $this->ip];
        if (null !== $key) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $key;
        }
        if (null !== $json) {
            $server['CONTENT_TYPE'] = 'application/ld+json';
        }

        $this->client->request(
            $method,
            $uri,
            server: $server,
            content: null === $json ? null : json_encode($json, JSON_THROW_ON_ERROR),
        );

        $content = (string) $this->client->getResponse()->getContent();
        if (!json_validate($content)) {
            return [];
        }

        /** @var array<string, mixed> */
        return json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return list<string>
     */
    protected static function memberNames(array $body): array
    {
        self::assertIsArray($body['member'] ?? null);
        /** @var list<array{name: string}> $members */
        $members = $body['member'];

        return array_column($members, 'name');
    }

    /** @param array<string, mixed> $body */
    protected static function violations(array $body): string
    {
        self::assertIsArray($body['violations'] ?? null);
        /** @var list<array{message: string}> $violations */
        $violations = $body['violations'];

        return implode(' | ', array_column($violations, 'message'));
    }

    protected function users(): UserRepository
    {
        $repository = self::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $repository);

        return $repository;
    }

    protected function categoryId(string $name): int
    {
        $repository = self::getContainer()->get(CategoryRepository::class);
        self::assertInstanceOf(CategoryRepository::class, $repository);
        foreach ($repository->findAllOrderedByName() as $category) {
            if ($category->getName() === $name) {
                return (int) $category->getId();
            }
        }

        self::fail(sprintf('Catégorie « %s » absente des fixtures.', $name));
    }
}
