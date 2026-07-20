<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Encrypt\KMS;

use Doctrine\ORM\Encrypt\KMS\ArraySymmetricKeyProvider;
use Doctrine\ORM\Encrypt\KMS\Exception\UnknownKey;
use Doctrine\ORM\Encrypt\KMS\Key;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function base64_encode;

#[CoversClass(ArraySymmetricKeyProvider::class)]
final class ArraySymmetricKeyProviderTest extends TestCase
{
    public function testConstructorRejectsEmptyKeys(): void
    {
        $this->expectException(UnknownKey::class);
        $this->expectExceptionMessage('Keys cannot be empty.');

        new ArraySymmetricKeyProvider([]);
    }

    public function testGetEncryptionKeyReturnsLastKey(): void
    {
        $provider = new ArraySymmetricKeyProvider([
            'v1' => new Key('v1', 'first-secret'),
            'v2' => new Key('v2', 'second-secret'),
        ]);

        $encryptionKey = $provider->getEncryptionKey();

        self::assertSame('v2', $encryptionKey->id);
        self::assertSame('second-secret', $encryptionKey->key);
    }

    public function testGetDecryptionKeyResolvesById(): void
    {
        $v1 = new Key('v1', 'first-secret');
        $v2 = new Key('v2', 'second-secret');

        $provider = new ArraySymmetricKeyProvider(['v1' => $v1, 'v2' => $v2]);

        self::assertSame($v1, $provider->getDecryptionKey('v1'));
        self::assertSame($v2, $provider->getDecryptionKey('v2'));
    }

    public function testGetDecryptionKeyUnknownId(): void
    {
        $provider = new ArraySymmetricKeyProvider(['v1' => new Key('v1', 'first-secret')]);

        $this->expectException(UnknownKey::class);
        $this->expectExceptionMessage('Could not resolve a key for id "v2".');

        $provider->getDecryptionKey('v2');
    }

    public function testFromScalarsDecodesBase64Material(): void
    {
        $provider = ArraySymmetricKeyProvider::fromScalars([
            'v1' => base64_encode('first-secret'),
            'v2' => base64_encode('second-secret'),
        ]);

        self::assertSame('first-secret', $provider->getDecryptionKey('v1')->key);
        self::assertSame('second-secret', $provider->getEncryptionKey()->key);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidBase64Provider(): iterable
    {
        yield 'invalid' => ['not-valid-base64!!'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalidBase64Provider')]
    public function testFromScalarsInvalidBase64(string $raw): void
    {
        $this->expectException(UnknownKey::class);
        $this->expectExceptionMessage('Could not init key for id "v1", invalid base64 string.');

        ArraySymmetricKeyProvider::fromScalars(['v1' => $raw]);
    }
}
