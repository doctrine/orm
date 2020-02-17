<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Exception;

use Doctrine\ORM\Exception\PersisterException;
use LogicException;

class InvalidOrientation extends LogicException implements PersisterException
{
    public static function fromClassNameAndField(string $className, string $field) : self
    {
        return new self('Invalid order by orientation specified for ' . $className . '#' . $field);
    }
}
