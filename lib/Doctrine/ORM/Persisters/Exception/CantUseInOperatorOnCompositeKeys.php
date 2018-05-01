<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Exception;

final class CantUseInOperatorOnCompositeKeys extends \LogicException implements PersisterException
{
    public static function new() : self
    {
        return new self("Can't use IN operator on entities that have composite keys.");
    }
}
