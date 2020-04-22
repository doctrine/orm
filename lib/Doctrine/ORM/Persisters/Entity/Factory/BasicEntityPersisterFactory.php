<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Entity\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\Persisters\Entity\JoinedSubclassPersister;
use Doctrine\ORM\Persisters\Entity\SingleTablePersister;

final class BasicEntityPersisterFactory implements EntityPersisterFactory
{
    /**
     * {@inheritDoc}
     */
    public function createBasic(EntityManagerInterface $em, ClassMetadata $class) : EntityPersister
    {
        return new BasicEntityPersister($em, $class);
    }

    /**
     * {@inheritDoc}
     */
    public function createSingleTable(EntityManagerInterface $em, ClassMetadata $class) : EntityPersister
    {
        return new SingleTablePersister($em, $class);
    }

    /**
     * {@inheritDoc}
     */
    public function createJoined(EntityManagerInterface $em, ClassMetadata $class) : EntityPersister
    {
        return new JoinedSubclassPersister($em, $class);
    }
}
