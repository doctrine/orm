<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Upsertable;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'insertable_column')]
class Insertable
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /** @var string */
    #[Column(type: 'string', insertable: false, options: ['default' => '1234'], generated: 'INSERT')]
    public $nonInsertableContent;

    /** @var string */
    #[Column(type: 'string', insertable: true)]
    public $insertableContent;

    public static function loadMetadata(ClassMetadata $metadata): ClassMetadata
    {
        $metadata->setPrimaryTable(
            ['name' => 'insertable_column'],
        );

        $metadata->mapField(
            [
                'id' => true,
                'fieldName' => 'id',
            ],
        );
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

        $metadata->mapField(
            [
                'fieldName' => 'nonInsertableContent',
                'notInsertable' => true,
                'options' => ['default' => '1234'],
                'generated' => ClassMetadata::GENERATED_INSERT,
            ],
        );
        $metadata->mapField(
            ['fieldName' => 'insertableContent'],
        );

        return $metadata;
    }
}
