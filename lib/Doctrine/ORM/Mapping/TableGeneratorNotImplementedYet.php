<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

final class TableGeneratorNotImplementedYet extends \Exception implements ORMException
{
    public static function create() : self
    {
        return new self('TableGenerator not yet implemented.');
    }
}
