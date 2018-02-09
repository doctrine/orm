<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\PersisterException;

class CantUseInOperatorOnCompositeKeys extends \Exception implements PersisterException
{
    public static function create() : self
    {
        return new self("Can't use IN operator on entities that have composite keys.");
    }
}
