<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\KMS\Exception;

use Doctrine\ORM\Exception\ORMException;
use InvalidArgumentException;

use function implode;
use function sprintf;

final class UnknownKeyProvider extends InvalidArgumentException implements ORMException
{
    /** @param list<string> $knownNames */
    public static function create(string $name, array $knownNames): self
    {
        return new self(sprintf(
            'Unknown key provider "%s". Known key providers: "%s".',
            $name,
            implode('", "', $knownNames),
        ));
    }
}
