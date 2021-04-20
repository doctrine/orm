<?php

namespace Doctrine\ORM\Tools\Console\EntityManagerProvider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Tools\Console\ConnectionProvider;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;

final class ConnectionFromManagerProvider implements ConnectionProvider
{
    /** @var EntityManagerProvider */
    private $entityManagerProvider;

    /** @var string */
    private $defaultManagerName;

    public function __construct(EntityManagerProvider $entityManagerProvider, $defaultManagerName = 'default')
    {
        $this->entityManagerProvider = $entityManagerProvider;
        $this->defaultManagerName = $defaultManagerName;
    }

    public function getDefaultConnection(): Connection
    {
        return $this->entityManagerProvider->getManager($this->defaultManagerName)->getConnection();
    }

    public function getConnection(string $name): Connection
    {
        return $this->entityManagerProvider->getManager($name)->getConnection();
    }
}
