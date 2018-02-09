<?php

declare(strict_types=1);

namespace Doctrine\ORM\EntityManager;

use Doctrine\ORM\ManagerException;

final class EntityManagerClosed extends \Exception implements ManagerException
{
    public static function create() : self
    {
        return new self('The EntityManager is closed.');
    }
}
