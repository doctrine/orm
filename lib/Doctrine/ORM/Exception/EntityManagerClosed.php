<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use Doctrine\ORM\Exception\ManagerException;

final class EntityManagerClosed extends \Exception implements ManagerException
{
    public static function create() : self
    {
        return new self('The EntityManager is closed.');
    }
}
