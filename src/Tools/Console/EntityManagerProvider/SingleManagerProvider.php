<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\EntityManagerProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;

final class SingleManagerProvider implements EntityManagerProvider
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $defaultManagerName = 'default',
    ) {
    }

    public function getDefaultManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getManager(string $name): EntityManagerInterface
    {
        if ($name !== $this->defaultManagerName) {
            throw UnknownManagerException::unknownManager($name, [$this->defaultManagerName]);
        }

        return $this->entityManager;
    }
}
