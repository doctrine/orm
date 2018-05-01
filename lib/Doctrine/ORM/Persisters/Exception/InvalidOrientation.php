<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Exception;

final class InvalidOrientation extends \LogicException implements PersisterException
{
    public static function fromClassNameAndField(string $className, string $field) : self
    {
        return new self('Invalid order by orientation specified for ' . $className . '#' . $field);
    }
}
