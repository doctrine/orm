<?php

declare(strict_types=1);

namespace Doctrine\ORM\Repository\Exception;

use Doctrine\ORM\Exception\RepositoryException;
use LogicException;

final class InvalidMagicMethodCall extends LogicException implements RepositoryException
{
    public static function becauseFieldNotFoundIn(
        string $entityName,
        string $fieldName,
        string $method,
    ): self {
        return new self(
            "Entity '" . $entityName . "' has no field '" . $fieldName . "'. " .
            "You can therefore not call '" . $method . "' on the entities' repository.",
        );
    }

    public static function onMissingParameter(string $methodName): self
    {
        return new self("You need to pass a parameter to '" . $methodName . "'");
    }
}
