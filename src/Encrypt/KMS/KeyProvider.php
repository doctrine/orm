<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\KMS;

use Doctrine\ORM\Encrypt\KMS\Exception\UnknownKey;

interface KeyProvider
{
    /**
     * The key all NEW ciphertexts must be produced with.
     */
    public function getEncryptionKey(): Key;

    /**
     * Get a decryption key from its id.
     * Implementations must be able to serve every key id that still exists in the database.
     *
     * @throws UnknownKey
     */
    public function getDecryptionKey(int|string $id): Key;
}
