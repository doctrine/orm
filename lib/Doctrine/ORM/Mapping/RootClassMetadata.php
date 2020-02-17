<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * @property MappedSuperClassMetadata $parent
 */
class RootClassMetadata extends EntityClassMetadata
{
    public function getRootClass() : RootClassMetadata
    {
        return $this;
    }
}
