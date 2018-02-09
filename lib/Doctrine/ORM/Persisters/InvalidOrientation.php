<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\PersisterException;

class InvalidOrientation extends \Exception implements PersisterException
{
    public static function fromClassNameAndField(string $className, string $field) : self
    {
        return new self('Invalid order by orientation specified for ' . $className . '#' . $field);
    }
}
