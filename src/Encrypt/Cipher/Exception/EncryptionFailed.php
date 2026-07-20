<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\Cipher\Exception;

use Doctrine\ORM\Exception\ORMException;
use RuntimeException;

use function sprintf;

final class EncryptionFailed extends RuntimeException implements ORMException
{
    public static function create(string $message): self
    {
        return new self(sprintf('Encryption failed: %s', $message));
    }
}
