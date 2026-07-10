<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Encrypt\KMS;

use Doctrine\ORM\Encrypt\KMS\AbstractKeyProviderRegistry;
use Doctrine\ORM\Encrypt\KMS\Exception\UnknownKeyProvider;
use Doctrine\Tests\Mocks\Encrypt\KMS\KeyProviderRegistryMock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractKeyProviderRegistry::class)]
final class AbstractKeyProviderRegistryTest extends TestCase
{
    public function testGetDefaultKeyProvider(): void
    {
        $registry = new KeyProviderRegistryMock();

        self::assertSame('alias', $registry->getDefaultKeyProviderName());
        self::assertEquals($registry->default, $registry->getKeyProvider());
    }

    public function testGetKeyProvider(): void
    {
        $registry = new KeyProviderRegistryMock();

        self::assertEquals($registry->default, $registry->getKeyProvider('alias'));
        self::assertEquals($registry->a, $registry->getKeyProvider('alias-a'));
        self::assertEquals($registry->b, $registry->getKeyProvider('alias-b'));
    }

    public function testGetKeyProviderUnknown(): void
    {
        $this->expectException(UnknownKeyProvider::class);
        $this->expectExceptionMessage('Unknown key provider "missing". Known key providers: "alias", "alias-a", "alias-b".');

        (new KeyProviderRegistryMock())->getKeyProvider('missing');
    }

    public function testConstructorUnknownDefault(): void
    {
        $this->expectException(UnknownKeyProvider::class);
        $this->expectExceptionMessage('Unknown key provider "missing". Known key providers: "alias", "alias-a", "alias-b".');

        new KeyProviderRegistryMock('missing');
    }

    public function testGetKeyProviderNames(): void
    {
        self::assertSame(['alias', 'alias-a', 'alias-b'], (new KeyProviderRegistryMock())->getKeyProviderNames());
    }

    public function testGetKeyProvidersReturnsAllIndexedByAlias(): void
    {
        $registry = new KeyProviderRegistryMock();

        self::assertSame(
            ['alias' => $registry->default, 'alias-a' => $registry->a, 'alias-b' => $registry->b],
            $registry->getKeyProviders(),
        );
    }
}
