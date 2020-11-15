<?php

namespace Doctrine\Tests\Models\DDC889;

use Doctrine\ORM\Mapping as ORM;

/**
 * @MappedSuperclass
 */
#[ORM\MappedSuperclass]
class DDC889SuperClass
{
    /** @Column() */
    #[ORM\Column]
    protected $name;

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadataInfo $metadata)
    {
        $metadata->mapField(
            [
           'fieldName'  => 'name',
            ]
        );

        $metadata->isMappedSuperclass = true;
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadataInfo::GENERATOR_TYPE_NONE);
    }
}
