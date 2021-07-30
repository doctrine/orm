<?php

declare(strict_types=1);

namespace Doctrine\StaticAnalysis\Mapping;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

class EntityManagerInterfaceTest
{
    /**
     * @return ClassMetadata<\DateTime>
     */
    public function testGetClassMetadata(EntityManagerInterface $entityManager): ClassMetadata
    {
        return $entityManager->getClassMetadata(\DateTime::class);
    }
}
