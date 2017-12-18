<?php

namespace Doctrine\ORM\Tools\Pagination;

final class RowNumberOverFunctionNotEnabled extends \Exception implements ORMException
{
    public function create()
    {
        throw new ORMException("The RowNumberOverFunction is not intended for, nor is it enabled for use in DQL.");
    }
}
