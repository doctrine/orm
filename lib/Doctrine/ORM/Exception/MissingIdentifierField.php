<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use LogicException;

use function sprintf;

final class MissingIdentifierField extends LogicException implements ManagerException
{
    public static function fromFieldAndClass(string $fieldName, string $className): self
    {
        return new self(sprintf(
            'The identifier %s is missing for a query of %s',
            $fieldName,
            $className,
        ));
    }
}
