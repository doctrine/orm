<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

class ManyToOneAssociationMetadata extends ToOneAssociationMetadata
{
    public function setOrphanRemoval(bool $orphanRemoval) : void
    {
        throw MappingException::illegalOrphanRemoval($this->getDeclaringClass()->getClassName(), $this->getName());
    }
}
