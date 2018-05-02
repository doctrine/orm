<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

final class PessimisticLockFailed extends \RuntimeException implements ORMException
{
    /**
     * @return PessimisticLockFailed
     */
    public static function lockFailed()
    {
        return new self('The pessimistic lock failed.');
    }
}
