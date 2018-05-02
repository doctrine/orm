<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Exception;

final class NotSupported extends \LogicException implements SchemaToolException
{
    public static function new() : self
    {
        return new self('This behaviour is (currently) not supported by Doctrine 2');
    }
}
