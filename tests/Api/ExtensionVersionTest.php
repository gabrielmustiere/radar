<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Service\ExtensionKeyManager;
use App\Service\ExtensionPackage;

final class ExtensionVersionTest extends ExtensionApiTestCase
{
    public function testValidKeyReadsTheAvailableVersion(): void
    {
        $body = $this->api('GET', '/api/extension/version', $this->keyFor($this->member()));

        self::assertResponseIsSuccessful();
        $package = self::getContainer()->get(ExtensionPackage::class);
        self::assertInstanceOf(ExtensionPackage::class, $package);
        self::assertSame($package->version(), $body['version'] ?? null);
    }

    public function testMissingKeyIsRefused(): void
    {
        $this->api('GET', '/api/extension/version', key: null);

        self::assertResponseStatusCodeSame(401);
    }

    public function testRevokedKeyIsRefused(): void
    {
        $member = $this->member();
        $key = $this->keyFor($member);
        $manager = self::getContainer()->get(ExtensionKeyManager::class);
        self::assertInstanceOf(ExtensionKeyManager::class, $manager);
        $manager->revoke($member);

        $this->api('GET', '/api/extension/version', $key);

        self::assertResponseStatusCodeSame(401);
    }
}
