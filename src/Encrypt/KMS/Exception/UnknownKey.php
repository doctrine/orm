<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\KMS\Exception;

use Doctrine\ORM\Exception\ORMException;
use InvalidArgumentException;

use function sprintf;

final class UnknownKey extends InvalidArgumentException implements ORMException
{
    public static function createNotResolveId(int|string $id): self
    {
        return new self(sprintf('Could not resolve a key for id "%s".', $id));
    }

    public static function createInvalidBase64(int|string $id): self
    {
        return new self(sprintf('Could not init key for id "%s", invalid base64 string.', $id));
    }

    public static function cannotBeEmpty(): self
    {
        return new self('Keys cannot be empty.');
    }
}
