<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Encrypt\Cipher;

use Doctrine\ORM\Encrypt\Cipher\AbstractCipherRegistry;
use Doctrine\ORM\Encrypt\Cipher\Exception\UnknownCipher;
use Doctrine\Tests\Mocks\Encrypt\Cipher\CipherRegistryMock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractCipherRegistry::class)]
final class AbstractCipherRegistryTest extends TestCase
{
    public function testGetDefaultCipher(): void
    {
        $registry = new CipherRegistryMock();

        self::assertSame('alias', $registry->getDefaultCipherName());
        self::assertEquals($registry->default, $registry->getCipher());
    }

    public function testGetCipher(): void
    {
        $registry = new CipherRegistryMock();

        self::assertEquals($registry->default, $registry->getCipher('alias'));
        self::assertEquals($registry->a, $registry->getCipher('alias-a'));
        self::assertEquals($registry->b, $registry->getCipher('alias-b'));
    }

    public function testGetCipherUnknown(): void
    {
        $this->expectException(UnknownCipher::class);
        $this->expectExceptionMessage('Unknown cipher "missing". Known ciphers: "alias", "alias-a", "alias-b".');

        (new CipherRegistryMock())->getCipher('missing');
    }

    public function testGetCipherUnknownDefault(): void
    {
        $this->expectException(UnknownCipher::class);
        $this->expectExceptionMessage('Unknown cipher "missing". Known ciphers: "alias", "alias-a", "alias-b".');

        new CipherRegistryMock('missing');
    }

    public function testGetCipherNamesReturnsAliases(): void
    {
        self::assertSame(['alias', 'alias-a', 'alias-b'], (new CipherRegistryMock())->getCipherNames());
    }

    public function testGetCiphersReturnsAllIndexedByAlias(): void
    {
        $registry = new CipherRegistryMock();

        self::assertSame(
            ['alias' => $registry->default, 'alias-a' => $registry->a, 'alias-b' => $registry->b],
            $registry->getCiphers(),
        );
    }
}
