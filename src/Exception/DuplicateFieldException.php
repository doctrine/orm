<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use LogicException;

use function sprintf;

class DuplicateFieldException extends LogicException implements ORMException
{
    public static function create(string $argName, string $columnName): self
    {
        return new self(sprintf('Name "%s" for "%s" already in use.', $argName, $columnName));
    }
}
