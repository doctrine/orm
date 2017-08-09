<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * Class SubClassMetadata
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 *
 * @property EntityClassMetadata $parent
 */
class SubClassMetadata extends EntityClassMetadata
{
    /**
     * @return RootClassMetadata
     */
    public function getRootClass() : RootClassMetadata
    {
        return $this->parent->getRootClass();
    }
}
