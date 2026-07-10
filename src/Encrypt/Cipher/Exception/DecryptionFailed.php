<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\Cipher\Exception;

use Doctrine\ORM\Exception\ORMException;
use RuntimeException;

use function sprintf;

final class DecryptionFailed extends RuntimeException implements ORMException
{
    public static function create(string $message): self
    {
        return new self(sprintf('Decryption failed: %s', $message));
    }

    public static function envelopTooShort(): self
    {
        return new self('Invalid ciphertext envelope: value is shorter than the minimum envelope size.');
    }
}
