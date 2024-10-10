<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use LogicException;

use function sprintf;

class NoMatchingPropertyException extends LogicException implements ORMException
{
    public static function create(string $property): self
    {
        return new self(sprintf('Column name "%s" does not match any property name. Consider aliasing it to the name of an existing property.', $property));
    }
}
