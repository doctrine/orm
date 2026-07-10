<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\Cipher;

use Doctrine\ORM\Encrypt\Cipher\Exception\DecryptionFailed;
use Doctrine\ORM\Encrypt\Cipher\Exception\EncryptionFailed;
use Doctrine\ORM\Encrypt\KMS\KeyProvider;
use SensitiveParameter;

/**
 * Implement this interface to create a service that handle encrypt/decrypt of a value.
 * IMPORTANT: ciphertext (envelope) MUST embed key's id.
 */
interface Cipher
{
    /**
     * Encrypts a plaintext string into a self-describing ciphertext (envelope): MUST embed key's id.
     *
     * @throws EncryptionFailed
     */
    public function encrypt(
        #[SensitiveParameter]
        string $plaintext,
        KeyProvider $keyProvider,
    ): string;

    /**
     * Decrypts an envelope produced by encrypt(), return plaintext.
     *
     * @throws DecryptionFailed on wrong key / tampered data / malformed input.
     */
    public function decrypt(
        #[SensitiveParameter]
        string $envelope,
        KeyProvider $keyProvider,
    ): string;

    /**
     * Extracts the key id embedded in an envelope produced by encrypt(), without decrypting it.
     *
     * @throws DecryptionFailed on malformed input.
     */
    public function extractKeyId(
        #[SensitiveParameter]
        string $envelope,
    ): int|string;

    /**
     * TRUE when the same (plaintext, key) pair always yields the identical ciphertext.
     */
    public function isDeterministic(): bool;

    /**
     * TRUE when encrypt() returns raw binary bytes (suitable for a blob column).
     * FALSE when it returns printable text (safe for a text column).
     */
    public function isBinary(): bool;
}
