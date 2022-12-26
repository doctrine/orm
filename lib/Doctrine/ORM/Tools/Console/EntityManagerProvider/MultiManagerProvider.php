<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\EntityManagerProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;

use function array_keys;

final class MultiManagerProvider implements EntityManagerProvider
{
    /** @var EntityManagerInterface[] */
    private $entityManagers;

    /** @var string */
    private $defaultManagerName;

    /** @param array<string, EntityManagerInterface> $entityManagers Available entity managers */
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
        if (! isset($this->entityManagers[$name])) {
            throw UnknownManagerException::unknownManager($name, array_keys($this->entityManagers));
        }

        return $this->entityManagers[$name];
    }
}
