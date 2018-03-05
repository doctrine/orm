<?php

declare(strict_types=1);

namespace Doctrine\ORM;

class PessimisticLockException extends \Exception implements ORMException
{
    /**
     * @return PessimisticLockException
     */
    public static function lockFailed()
    {
        return new self('The pessimistic lock failed.');
    }
}
