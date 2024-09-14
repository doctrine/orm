<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use function implode;
use function sprintf;

final class UnrecognizedIdentifierFields extends ORMException implements ManagerException
{
    /** @param string[] $fieldNames */
    public static function fromClassAndFieldNames(string $className, array $fieldNames): self
    {
        return new self(sprintf(
            'Unrecognized identifier fields: "%s" are not present on class "%s".',
            implode("', '", $fieldNames),
            $className
        ));
    }
}
