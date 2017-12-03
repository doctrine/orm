<?php

declare(strict_types=1);

namespace Doctrine\ORM\EntityManager;

use Doctrine\ORM\ManagerException;

final class MissingIdentifierField extends \Exception implements ManagerException
{
    public static function fromFieldAndClass($fieldName, $className) : self
    {
        return new self("The identifier $fieldName is missing for a query of $className");
    }
}
