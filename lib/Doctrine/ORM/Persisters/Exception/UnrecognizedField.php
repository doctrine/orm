<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Exception;

use Doctrine\ORM\Exception\PersisterException;

use function sprintf;

final class UnrecognizedField extends PersisterException
{
    /** @deprecated Use {@see byFullyQualifiedName()} instead. */
    public static function byName(string $field): self
    {
        return new self(sprintf('Unrecognized field: %s', $field));
    }

    /** @param class-string $className */
    public static function byFullyQualifiedName(string $className, string $field): self
    {
        return new self(sprintf('Unrecognized field: %s::$%s', $className, $field));
    }
}
