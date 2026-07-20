<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\Cipher;

use Doctrine\ORM\Encrypt\Cipher\Exception\DecryptionFailed;
use Doctrine\ORM\Encrypt\Cipher\Exception\EncryptionFailed;
use Doctrine\ORM\Encrypt\KMS\KeyProvider;
use LogicException;
use SensitiveParameter;

use function array_keys;
use function chr;
use function hash_hmac;
use function implode;
use function openssl_cipher_iv_length;
use function openssl_decrypt;
use function openssl_encrypt;
use function openssl_error_string;
use function ord;
use function random_bytes;
use function sprintf;
use function strlen;
use function substr;

use const OPENSSL_RAW_DATA;

/**
 * AES-GCM cipher (algorithm configurable among the AES-*-GCM family), with a self-describing
 * envelope so decrypt() can locate the KMS key used for a given ciphertext (key rotation).
 *
 * Random mode (default) uses a random IV per encryption. Deterministic mode derives a
 * synthetic IV from an HMAC-SHA256 of the plaintext keyed with the raw KMS key, so the
 * same (plaintext, key) pair always produces the same envelope — required for
 * equality-queryable fields.
 */
final class OpenSSLCipher implements Cipher
{
    public const AES_128_GCM = 'aes-128-gcm';
    public const AES_192_GCM = 'aes-192-gcm';
    public const AES_256_GCM = 'aes-256-gcm';

    private const TAG_LENGTH = 16;

    private const SUPPORTED_ALGORITHMS = [
        self::AES_128_GCM => 16,
        self::AES_192_GCM => 24,
        self::AES_256_GCM => 32,
    ];

    private readonly int $keyLength;

    /** @var positive-int */
    private readonly int $ivLength;

    public function __construct(
        private readonly string $algorithm = 'aes-256-gcm',
        private readonly bool $deterministic = false,
    ) {
        if (! isset(self::SUPPORTED_ALGORITHMS[$this->algorithm])) {
            throw new LogicException(sprintf(
                'Unsupported algorithm "%s". Supported algorithms: "%s".',
                $this->algorithm,
                implode('", "', array_keys(self::SUPPORTED_ALGORITHMS)),
            ));
        }

        $ivLength = openssl_cipher_iv_length($this->algorithm);
        if ($ivLength === false || $ivLength < 1) {
            throw new LogicException(sprintf(
                'Unsupported algorithm "%s". Supported algorithms: "%s".',
                $this->algorithm,
                implode('", "', array_keys(self::SUPPORTED_ALGORITHMS)),
            ));
        }

        $this->keyLength = self::SUPPORTED_ALGORITHMS[$this->algorithm];
        $this->ivLength  = $ivLength;
    }

    public function encrypt(
        #[SensitiveParameter]
        string $plaintext,
        KeyProvider $keyProvider,
    ): string {
        $key         = $keyProvider->getEncryptionKey();
        $keyIdBytes  = (string) $key->id;
        $keyIdLength = strlen($keyIdBytes);

        if ($keyIdLength === 0 || $keyIdLength > 255) {
            throw EncryptionFailed::create(sprintf(
                'Key id must be a non-empty string of at most 255 UTF-8 bytes, got %d bytes.',
                $keyIdLength,
            ));
        }

        $rawKey = $key->key;

        if (strlen($rawKey) !== $this->keyLength) {
            throw EncryptionFailed::create(sprintf(
                'Expected a %d-byte key, got %d bytes.',
                $this->keyLength,
                strlen($rawKey),
            ));
        }

        $header = chr($keyIdLength) . $keyIdBytes;
        $iv     = $this->deterministic
            ? substr(hash_hmac('sha256', $plaintext, $rawKey, true), 0, $this->ivLength)
            : random_bytes($this->ivLength);

        $tag        = '';
        $ciphertext = openssl_encrypt($plaintext, $this->algorithm, $rawKey, OPENSSL_RAW_DATA, $iv, $tag, $header, self::TAG_LENGTH);

        if ($ciphertext === false) {
            throw EncryptionFailed::create(openssl_error_string() ?: 'unknown error');
        }

        return $header . $iv . $tag . $ciphertext;
    }

    public function decrypt(
        #[SensitiveParameter]
        string $envelope,
        KeyProvider $keyProvider,
    ): string {
        $keyId          = $this->extractKeyId($envelope);
        $headerLength   = 1 + strlen($keyId);
        $envelopeLength = $headerLength + $this->ivLength + self::TAG_LENGTH;

        if (strlen($envelope) < $envelopeLength) {
            throw DecryptionFailed::envelopTooShort();
        }

        $header        = substr($envelope, 0, $headerLength);
        $iv            = substr($envelope, $headerLength, $this->ivLength);
        $tag           = substr($envelope, $headerLength + $this->ivLength, self::TAG_LENGTH);
        $rawCiphertext = substr($envelope, $envelopeLength);

        $key    = $keyProvider->getDecryptionKey($keyId);
        $rawKey = $key->key;

        if (strlen($rawKey) !== $this->keyLength) {
            throw DecryptionFailed::create(sprintf(
                'Expected a %d-byte key, got %d bytes.',
                $this->keyLength,
                strlen($rawKey),
            ));
        }

        $plaintext = openssl_decrypt($rawCiphertext, $this->algorithm, $rawKey, OPENSSL_RAW_DATA, $iv, $tag, $header);

        if ($plaintext === false) {
            throw DecryptionFailed::create(openssl_error_string() ?: 'unknown error');
        }

        return $plaintext;
    }

    public function extractKeyId(
        #[SensitiveParameter]
        string $envelope,
    ): string {
        if (strlen($envelope) < 1) {
            throw DecryptionFailed::envelopTooShort();
        }

        $keyIdLength = ord($envelope[0]);

        if ($keyIdLength === 0 || strlen($envelope) < 1 + $keyIdLength) {
            throw DecryptionFailed::envelopTooShort();
        }

        return substr($envelope, 1, $keyIdLength);
    }

    public function isDeterministic(): bool
    {
        return $this->deterministic;
    }

    public function isBinary(): bool
    {
        return true;
    }
}
