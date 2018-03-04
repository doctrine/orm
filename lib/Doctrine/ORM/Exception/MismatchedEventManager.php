<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use Doctrine\ORM\Exception\ManagerException;

final class MismatchedEventManager extends \Exception implements ManagerException
{
    public static function create() : self
    {
        return new self(
            'Cannot use different EventManager instances for EntityManager and Connection.'
        );
    }
}
