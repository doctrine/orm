<?php

declare(strict_types=1);

namespace Doctrine\ORM\Repository\Exception;

use Doctrine\ORM\Exception\RepositoryException;
use LogicException;

final class InvalidFindByCall extends LogicException implements RepositoryException
{
    public static function fromInverseSideUsage(
        string $entityName,
        string $associationFieldName,
    ): self {
        return new self(
            "You cannot search for the association field '" . $entityName . '#' . $associationFieldName . "', " .
            'because it is the inverse side of an association. Find methods only work on owning side associations.',
        );
    }
}
