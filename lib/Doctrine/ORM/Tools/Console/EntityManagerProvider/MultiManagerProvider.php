<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\EntityManagerProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;

final class MultiManagerProvider implements EntityManagerProvider
{
    /** @var EntityManagerInterface[] */
    private $entityManagers;

    /** @var string */
    private $defaultManagerName;

    public function __construct(array $entityManagers, string $defaultManagerName = 'default')
    {
        $this->entityManagers     = $entityManagers;
        $this->defaultManagerName = $defaultManagerName;
    }

    public function getDefaultManager(): EntityManagerInterface
    {
        return $this->entityManagers[$this->defaultManagerName];
    }

    public function getManager(string $name): EntityManagerInterface
    {
        if (!isset($this->entityManagers[$name])) {
            throw UnknownManagerException::unknownManager($name, $this->entityManagers);
        }

        return $this->entityManagers[$name];
    }
}
