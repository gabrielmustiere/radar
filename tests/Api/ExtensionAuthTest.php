<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class ExtensionAuthTest extends ExtensionApiTestCase
{
    public function testValidKeyIsAcceptedAndItsUseRecorded(): void
    {
        $member = $this->member();
        $key = $this->keyFor($member);

        $body = $this->api('GET', '/api/extension/categories', $key);

        self::assertResponseIsSuccessful();
        self::assertContains('Agents', self::memberNames($body));
        self::assertNotNull($this->reload($member)->getExtensionKeyLastUsedAt());
    }

    public function testMissingKeyIsRejected(): void
    {
        $this->api('GET', '/api/extension/categories', key: null);

        self::assertResponseStatusCodeSame(401);
    }

    public function testUnknownKeyIsRejected(): void
    {
        $this->api('GET', '/api/extension/categories', 'rdr_' . str_repeat('0', times: 64));

        self::assertResponseStatusCodeSame(401);
    }

    public function testRevokedKeyIsRejected(): void
    {
        $member = $this->member();
        $key = $this->keyFor($member);
        $member->revokeExtensionKey();
        $this->users()->save($member);

        $this->api('GET', '/api/extension/categories', $key);

        self::assertResponseStatusCodeSame(401);
    }

    public function testRegeneratedKeyReplacesThePreviousOne(): void
    {
        $member = $this->member();
        $previous = $this->keyFor($member);
        $current = $this->keyFor($member);

        $this->api('GET', '/api/extension/categories', $previous);
        self::assertResponseStatusCodeSame(401);

        $this->api('GET', '/api/extension/categories', $current);
        self::assertResponseIsSuccessful();
    }

    public function testKeyIdleForMoreThanNinetyDaysIsRejected(): void
    {
        $key = $this->keyLastUsed('-91 days');

        $this->api('GET', '/api/extension/categories', $key);

        self::assertResponseStatusCodeSame(401);
    }

    public function testKeyUsedEightyNineDaysAgoStillWorks(): void
    {
        $key = $this->keyLastUsed('-89 days');

        $this->api('GET', '/api/extension/categories', $key);

        self::assertResponseIsSuccessful();
    }

    private function keyLastUsed(string $modifier): string
    {
        $member = $this->member();
        $key = $this->keyFor($member);
        $member->markExtensionKeyUsed(new \DateTimeImmutable($modifier));
        $this->users()->save($member);

        return $key;
    }

    /** Membre relu en base, hors du cache de l'unité de travail. */
    private function reload(User $user): User
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();
        $reloaded = $this->users()->find($user->getId());
        self::assertNotNull($reloaded);

        return $reloaded;
    }
}
