<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use Doctrine\ORM\Exception\ManagerException;

final class UnrecognizedIdentifierFields extends \Exception implements ManagerException
{
    public static function fromClassAndFieldNames(string $className, array $fieldNames) : self
    {
        return new self(
            "Unrecognized identifier fields: '" . implode("', '", $fieldNames) . "' " .
            "are not present on class '$className'."
        );
    }
}
