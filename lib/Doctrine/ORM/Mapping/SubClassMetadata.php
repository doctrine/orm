<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * @property EntityClassMetadata $parent
 */
class SubClassMetadata extends EntityClassMetadata
{
    public function getRootClass() : RootClassMetadata
    {
        return $this->parent->getRootClass();
    }
}
