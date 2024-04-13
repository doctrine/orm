<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Upsertable;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="updatable_column")
 */
#[Entity]
#[Table(name: 'updatable_column')]
class Updatable
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /**
     * @var string
     * @Column(type="string", length=255, name="non_updatable_content", updatable=false, generated="ALWAYS")
     */
    #[Column(name: 'non_updatable_content', type: 'string', length: 255, updatable: false, generated: 'ALWAYS')]
    public $nonUpdatableContent;

    /**
     * @var string
     * @Column(type="string", length=255, updatable=true)
     */
    #[Column(type: 'string', length: 255, updatable: true)]
    public $updatableContent;

    public static function loadMetadata(ClassMetadata $metadata): ClassMetadata
    {
        $metadata->setPrimaryTable(
            ['name' => 'updatable_column']
        );

        $metadata->mapField(
            [
                'id' => true,
                'fieldName' => 'id',
            ]
        );
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

        $metadata->mapField(
            [
                'fieldName' => 'nonUpdatableContent',
                'notUpdatable' => true,
                'generated' => ClassMetadata::GENERATED_ALWAYS,
            ]
        );
        $metadata->mapField(
            ['fieldName' => 'updatableContent']
        );

        return $metadata;
    }
}
