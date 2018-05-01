<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

final class EntityManagerClosed extends \RuntimeException implements ManagerException
{
    public static function new() : self
    {
        return new self('The EntityManager is closed.');
    }
}
