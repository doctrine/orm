<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC889;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * @MappedSuperclass
 */
class DDC889SuperClass
{
    /**
     * @var string
     * @Column()
     */
    protected $name;

    public static function loadMetadata(ClassMetadataInfo $metadata): void
    {
        $metadata->mapField(
            ['fieldName' => 'name']
        );

        $metadata->isMappedSuperclass = true;
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);
    }
}
