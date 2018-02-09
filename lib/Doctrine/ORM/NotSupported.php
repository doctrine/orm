<?php

declare(strict_types=1);

namespace Doctrine\ORM;

final class NotSupported extends \Exception implements ORMException
{
    public static function create() : self
    {
        return new self('This behaviour is (currently) not supported by Doctrine 2');
    }
}
