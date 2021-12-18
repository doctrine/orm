<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Exception;

use Doctrine\ORM\Exception\PersisterException;

class CantUseInOperatorOnCompositeKeys extends PersisterException
{
    public static function create(): self
    {
        return new self("Can't use IN operator on entities that have composite keys.");
    }
}
