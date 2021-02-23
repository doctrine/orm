<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use LogicException;

final class CommitInsideCommit extends LogicException implements ManagerException
{
    public static function create() : self
    {
        return new self('UnitOfWork::commit() must not be called inside another UnitOfWork::commit() call');
    }
}
