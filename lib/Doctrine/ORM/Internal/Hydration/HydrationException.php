<?php

namespace Doctrine\ORM\Internal\Hydration;

class HydrationException extends \Doctrine\Common\DoctrineException
{
    public static function nonUniqueResult()
    {
        return new self("The result returned by the query was not unique.");
    }
}