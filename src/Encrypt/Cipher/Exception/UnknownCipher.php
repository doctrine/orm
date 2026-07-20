<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\Cipher\Exception;

use Doctrine\ORM\Exception\ORMException;
use InvalidArgumentException;

use function implode;
use function sprintf;

final class UnknownCipher extends InvalidArgumentException implements ORMException
{
    /** @param list<string> $knownNames */
    public static function create(string $name, array $knownNames): self
    {
        return new self(sprintf(
            'Unknown cipher "%s". Known ciphers: "%s".',
            $name,
            implode('", "', $knownNames),
        ));
    }
}
