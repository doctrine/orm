<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Encrypt\Cipher;

use Doctrine\ORM\Encrypt\Cipher\Exception\DecryptionFailed;
use Doctrine\ORM\Encrypt\Cipher\Exception\EncryptionFailed;
use Doctrine\ORM\Encrypt\Cipher\OpenSSLCipher;
use Doctrine\ORM\Encrypt\KMS\ArraySymmetricKeyProvider;
use Doctrine\ORM\Encrypt\KMS\Key;
use Doctrine\ORM\Encrypt\KMS\KeyProvider;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function random_bytes;
use function strlen;
use function substr;

#[CoversClass(OpenSSLCipher::class)]
final class OpenSSLCipherTest extends TestCase
{
    /** @return iterable<string, array{string, int}> */
    public static function algorithmsProvider(): iterable
    {
        yield OpenSSLCipher::AES_128_GCM => [OpenSSLCipher::AES_128_GCM, 16];
        yield OpenSSLCipher::AES_192_GCM => [OpenSSLCipher::AES_192_GCM, 24];
        yield OpenSSLCipher::AES_256_GCM => [OpenSSLCipher::AES_256_GCM, 32];
    }

    /** @param positive-int $keyLength */
    #[DataProvider('algorithmsProvider')]
    public function testEncryptDecryptSupportedAlgorithm(string $algorithm, int $keyLength): void
    {
        $cipher   = new OpenSSLCipher($algorithm);
        $provider = $this->getKeyProvider($keyLength);

        $envelope = $cipher->encrypt('hello world', $provider);

        self::assertNotSame('hello world', $envelope);
        self::assertSame('hello world', $cipher->decrypt($envelope, $provider));
    }

    public function testNotDeterministic(): void
    {
        $cipher   = new OpenSSLCipher(deterministic: false);
        $provider = $this->getKeyProvider();

        self::assertNotSame(
            $cipher->encrypt('hello world', $provider),
            $cipher->encrypt('hello world', $provider),
        );
    }

    public function testDeterministic(): void
    {
        $cipher   = new OpenSSLCipher(deterministic: true);
        $provider = $this->getKeyProvider();

        self::assertSame(
            $cipher->encrypt('hello world', $provider),
            $cipher->encrypt('hello world', $provider),
        );
    }

    public function testDeterministicDifferentPlaintext(): void
    {
        $cipher   = new OpenSSLCipher(deterministic: true);
        $provider = $this->getKeyProvider();

        self::assertNotSame(
            $cipher->encrypt('hello world', $provider),
            $cipher->encrypt('goodbye world', $provider),
        );
    }

    public function testDecryptAfterRotatedKey(): void
    {
        $cipher = new OpenSSLCipher();
        $keyV1  = new Key('v1', random_bytes(32));
        $keyV2  = new Key('v2', random_bytes(32));

        $envelope = $cipher->encrypt('hello world', new ArraySymmetricKeyProvider(['v1' => $keyV1]));

        $rotatedProvider = new ArraySymmetricKeyProvider(['v1' => $keyV1, 'v2' => $keyV2]);

        self::assertSame('hello world', $cipher->decrypt($envelope, $rotatedProvider));
    }

    public function testExtractKeyId(): void
    {
        $cipher   = new OpenSSLCipher();
        $provider = $this->getKeyProvider();

        $envelope = $cipher->encrypt('hello world', $provider);

        self::assertSame('v1', $cipher->extractKeyId($envelope));
    }

    public function testExtractKeyIdRejectsTooShortEnvelope(): void
    {
        $this->expectException(DecryptionFailed::class);
        $this->expectExceptionMessage('Invalid ciphertext envelope: value is shorter than the minimum envelope size.');

        (new OpenSSLCipher())->extractKeyId('x');
    }

    public function testConstructorUnsupportedAlgorithm(): void
    {
        $this->expectException(LogicException::class);

        new OpenSSLCipher('aes-256-cbc');
    }

    public function testEncryptWrongKeyLength(): void
    {
        $cipher   = new OpenSSLCipher('aes-256-gcm');
        $provider = new ArraySymmetricKeyProvider(['v1' => new Key('v1', 'too-short')]);

        $this->expectException(EncryptionFailed::class);
        $this->expectExceptionMessage('Encryption failed: Expected a 32-byte key, got 9 bytes.');

        $cipher->encrypt('hello world', $provider);
    }

    public function testEncryptEmptyKeyId(): void
    {
        $cipher   = new OpenSSLCipher();
        $provider = new ArraySymmetricKeyProvider(['' => new Key('', random_bytes(32))]);

        $this->expectException(EncryptionFailed::class);
        $this->expectExceptionMessage('Encryption failed: Key id must be a non-empty string of at most 255 UTF-8 bytes, got 0 bytes.');

        $cipher->encrypt('hello world', $provider);
    }

    public function testDecryptTooShortEnvelope(): void
    {
        $cipher   = new OpenSSLCipher();
        $provider = $this->getKeyProvider();

        $this->expectException(DecryptionFailed::class);
        $this->expectExceptionMessage('Invalid ciphertext envelope: value is shorter than the minimum envelope size.');

        $cipher->decrypt('x', $provider);
    }

    public function testDecryptTamperedCiphertext(): void
    {
        $cipher   = new OpenSSLCipher();
        $provider = $this->getKeyProvider();

        $envelope = $cipher->encrypt('hello world', $provider);
        $lastByte = $envelope[strlen($envelope) - 1];
        $flipped  = $lastByte === "\x00" ? "\x01" : "\x00";
        $tampered = substr($envelope, 0, -1) . $flipped;

        $this->expectException(DecryptionFailed::class);
        $this->expectExceptionMessage('Decryption failed: unknown error');

        $cipher->decrypt($tampered, $provider);
    }

    public function testDecryptRejectsWrongKey(): void
    {
        $cipher = new OpenSSLCipher();

        $envelope = $cipher->encrypt('hello world', $this->getKeyProvider());

        $wrongKeyProvider = $this->getKeyProvider();

        $this->expectException(DecryptionFailed::class);
        $this->expectExceptionMessage('Decryption failed: unknown error');

        $cipher->decrypt($envelope, $wrongKeyProvider);
    }

    public function testIsDeterministic(): void
    {
        self::assertFalse((new OpenSSLCipher())->isDeterministic());
        self::assertFalse((new OpenSSLCipher(deterministic: false))->isDeterministic());
        self::assertTrue((new OpenSSLCipher(deterministic: true))->isDeterministic());
    }

    public function testIsBinary(): void
    {
        self::assertTrue((new OpenSSLCipher())->isBinary());
    }

    private function getKeyProvider(int $keyLength = 32): KeyProvider
    {
        return new ArraySymmetricKeyProvider(['v1' => new Key('v1', random_bytes($keyLength))]);
    }
}
