<?php

declare(strict_types=1);

namespace Doctrine\ORM\EntityManager;

use Doctrine\ORM\ManagerException;

final class MismatchedEventManager extends \Exception implements ManagerException
{
    public static function create() : self
    {
        return new self(
            'Cannot use different EventManager instances for EntityManager and Connection.'
        );
    }
}
