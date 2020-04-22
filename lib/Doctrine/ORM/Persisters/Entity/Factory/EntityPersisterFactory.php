<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Entity\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;

interface EntityPersisterFactory
{
    /**
     * Creates a basic entity persister
     */
    public function createBasic(EntityManagerInterface $em, ClassMetadata $class) : EntityPersister;

    /**
     * Creates a single table entity persister
     */
    public function createSingleTable(EntityManagerInterface $em, ClassMetadata $class) : EntityPersister;

    /**
     * Creates a joined subclass entity persister
     */
    public function createJoined(EntityManagerInterface $em, ClassMetadata $class) : EntityPersister;
}
