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
     * SubClassMetadata constructor.
     *
     * @param string              $className
     * @param EntityClassMetadata $parent
     */
    public function __construct(string $className, EntityClassMetadata $parent)
    {
        parent::__construct($className, $parent);
    }

    /**
     * @return RootClassMetadata
     */
    public function getRootClass() : RootClassMetadata
    {
        return $this->parent->getRootClass();
    }
}
