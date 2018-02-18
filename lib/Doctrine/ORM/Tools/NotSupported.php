<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\SchemaToolException;

final class NotSupported extends \Exception implements SchemaToolException
{
    public static function create() : self
    {
        return new self('This behaviour is (currently) not supported by Doctrine 2');
    }
}
