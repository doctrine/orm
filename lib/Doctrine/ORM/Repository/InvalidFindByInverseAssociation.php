<?php

declare(strict_types=1);

namespace Doctrine\ORM\Repository;

use Doctrine\ORM\RepositoryException;

final class InvalidFindByInverseAssociation extends \BadMethodCallException implements RepositoryException
{
    public static function becauseIsInverseAssociation(
        string $entityName,
        string $associationFieldName
    ) : self {
        return new self(
            "You cannot search for the association field '".$entityName."#".$associationFieldName."', ".
            "because it is the inverse side of an association. Find methods only work on owning side associations."
        );
    }
}
