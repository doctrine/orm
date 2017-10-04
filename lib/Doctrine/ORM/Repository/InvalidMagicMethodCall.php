<?php

declare(strict_types=1);

namespace Doctrine\ORM\Repository;

use Doctrine\ORM\RepositoryException;

final class InvalidMagicMethodCall extends \InvalidArgumentException implements RepositoryException
{
    public static function fromEntityNameFieldNameAndMethod(
        string $entityName,
        string $fieldName,
        string $method
    ) : self {
        return new self(
            "Entity '".$entityName."' has no field '".$fieldName."'. ".
            "You can therefore not call '".$method."' on the entities' repository."
        );
    }
}
