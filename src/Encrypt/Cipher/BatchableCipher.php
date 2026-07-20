<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\Cipher;

use Doctrine\ORM\Encrypt\Cipher\Exception\DecryptionFailed;
use Doctrine\ORM\Encrypt\Cipher\Exception\EncryptionFailed;
use Doctrine\ORM\Encrypt\KMS\KeyProvider;
use SensitiveParameter;

/**
 * Cipher can implement this interface to handle batch encrypt/decrypt.
 * The ORM will use it preferably, if your Cipher implement it.
 * Useful when using remote Cipher service.
 */
interface BatchableCipher extends Cipher
{
    /**
     * Encrypts many values in one call. The returned array MUST preserve the input keys.
     * Every value MUST be a non-null string.
     *
     * @param array<array-key, string> $plaintexts
     *
     * @return array<array-key, string>
     *
     * @throws EncryptionFailed
     */
    public function encryptMany(
        #[SensitiveParameter]
        array $plaintexts,
        KeyProvider $keyProvider,
    ): array;

    /**
     * Decrypts many values in one call. The returned array MUST preserve the input keys.
     *
     * @param array<array-key, string> $ciphertexts
     *
     * @return array<array-key, string>
     *
     * @throws DecryptionFailed
     */
    public function decryptMany(
        #[SensitiveParameter]
        array $ciphertexts,
        KeyProvider $keyProvider,
    ): array;
}
