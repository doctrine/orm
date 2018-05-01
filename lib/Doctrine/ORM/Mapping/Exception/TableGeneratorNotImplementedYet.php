<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exception;

use Doctrine\ORM\Exception\ORMException;

final class TableGeneratorNotImplementedYet extends \LogicException implements ORMException
{
    public static function new() : self
    {
        return new self('TableGenerator not yet implemented.');
    }
}
