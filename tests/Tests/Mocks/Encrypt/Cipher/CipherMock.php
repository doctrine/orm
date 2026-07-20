<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks\Encrypt\Cipher;

use Doctrine\ORM\Encrypt\Cipher\Cipher;
use Doctrine\ORM\Encrypt\KMS\KeyProvider;

final class CipherMock implements Cipher
{
    public function encrypt(string $plaintext, KeyProvider $keyProvider): string
    {
        return $plaintext;
    }

    public function decrypt(string $envelope, KeyProvider $keyProvider): string
    {
        return $envelope;
    }

    public function extractKeyId(string $envelope): string
    {
        return 'mock-key';
    }

    public function isDeterministic(): bool
    {
        return false;
    }

    public function isBinary(): bool
    {
        return false;
    }
}
