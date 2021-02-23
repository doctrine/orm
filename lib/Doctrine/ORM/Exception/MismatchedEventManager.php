<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use LogicException;

final class MismatchedEventManager extends LogicException implements ManagerException
{
    public static function create() : self
    {
        return new self(
            'Cannot use different EventManager instances for EntityManager and Connection.'
        );
    }
}
