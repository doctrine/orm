<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\ORM\ORMException;

final class RowNumberOverFunctionNotEnabled extends \Exception implements ORMException
{
    public static function create()
    {
        throw new ORMException('The RowNumberOverFunction is not intended for, nor is it enabled for use in DQL.');
    }
}
