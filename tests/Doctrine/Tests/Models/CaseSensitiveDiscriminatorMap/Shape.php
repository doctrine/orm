<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CaseSensitiveDiscriminatorMap;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorMap({"cube" = cube::class})
 * @DiscriminatorColumn(name="discr", length=32, type="string")
 */
abstract class Shape
{
    /** @Id @Column(type="string") @GeneratedValue(strategy="AUTO") */
    public $id;

    public static function loadMetadata(ClassMetadataInfo $metadata)
    {
        $metadata->setInheritanceType(ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE);
        $metadata->setDiscriminatorColumn([
            'name' => 'discr',
            'type' => 'string',
            'length' => 32,
        ]);
        $metadata->setDiscriminatorMap([
            'cube' => cube::class,
        ]);
        $metadata->mapField([
            'fieldName' => 'id',
            'type' => 'string',
            'length' => null,
            'precision' => 0,
            'scale' => 0,
            'nullable' => false,
            'unique' => false,
            'id' => true,
            'columnName' => 'id',
        ]);
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
    }
}
