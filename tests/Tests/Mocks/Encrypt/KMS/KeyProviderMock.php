<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks\Encrypt\KMS;

use Doctrine\ORM\Encrypt\KMS\Key;
use Doctrine\ORM\Encrypt\KMS\KeyProvider;

final class KeyProviderMock implements KeyProvider
{
    public function __construct(
        private readonly string $keyMaterial,
    ) {
    }

    public function getEncryptionKey(): Key
    {
        return new Key('current', $this->keyMaterial);
    }

    public function getDecryptionKey(int|string $id): Key
    {
        return new Key($id, $this->keyMaterial);
    }
}
