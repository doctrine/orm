<?php

namespace Doctrine\Tests\Models\DDC889;

use Doctrine\DBAL\Types\Type;

/**
 * @MappedSuperclass
 */
class DDC889SuperClass
{

    /** @Column() */
    protected $name;

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $metadata->addProperty('name', Type::getType('string'));

        $metadata->isMappedSuperclass = true;

        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
    }
}
