<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\PersisterException;

final class UnrecognizedField extends \Exception implements PersisterException
{
    public static function byName(string $field) : self
    {
        return new self("Unrecognized field: $field");
    }
}
