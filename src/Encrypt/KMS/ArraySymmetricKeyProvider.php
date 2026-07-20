<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\KMS;

use Doctrine\ORM\Encrypt\KMS\Exception\UnknownKey;

use function array_key_last;
use function base64_decode;
use function count;

final class ArraySymmetricKeyProvider implements KeyProvider
{
    /** @param array<int|string, Key> $keys Indexed by key id. */
    public function __construct(
        private readonly array $keys,
    ) {
        if (count($this->keys) === 0) {
            throw UnknownKey::cannotBeEmpty();
        }
    }

    /** @param array<int|string, string> $keys Base64-encoded key material, indexed by key id. */
    public static function fromScalars(array $keys): self
    {
        $decoded = [];
        foreach ($keys as $id => $base64) {
            $raw = base64_decode($base64, true);
            if ($raw === false || $raw === '') {
                throw UnknownKey::createInvalidBase64($id);
            }

            $decoded[$id] = new Key($id, $raw);
        }

        return new self($decoded);
    }

    public function getEncryptionKey(): Key
    {
        return $this->keys[array_key_last($this->keys)];
    }

    public function getDecryptionKey(int|string $id): Key
    {
        return $this->keys[$id] ?? throw UnknownKey::createNotResolveId($id);
    }
}
