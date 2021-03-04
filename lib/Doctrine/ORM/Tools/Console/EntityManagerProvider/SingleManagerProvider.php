<?php

namespace Doctrine\ORM\Tools\Console\EntityManagerProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;

class SingleManagerProvider implements EntityManagerProvider
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getManager(string $name = 'default'): EntityManagerInterface
    {
        if ($name !== 'default') {
            throw UnknownManagerException::unknownManager($name, ['default']);
        }

        return $this->entityManager;
    }
}
