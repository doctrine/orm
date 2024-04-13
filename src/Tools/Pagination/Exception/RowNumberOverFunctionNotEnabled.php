<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination\Exception;

use Doctrine\ORM\Exception\ORMException;

final class RowNumberOverFunctionNotEnabled extends ORMException
{
    public static function create(): self
    {
        return new self('The RowNumberOverFunction is not intended for, nor is it enabled for use in DQL.');
    }
}
