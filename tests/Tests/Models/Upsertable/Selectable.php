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
#[Table(name: 'selectable_column')]
class Selectable
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /** @var string */
    #[Column(name: 'non_selectable_content', type: 'string', length: 255, selectable: false)]
    public $nonSelectableContent;

    /** @var string */
    #[Column(type: 'string', length: 255, selectable: true)]
    public $selectableContent;

    public static function loadMetadata(ClassMetadata $metadata): ClassMetadata
    {
        $metadata->setPrimaryTable(
            ['name' => 'selectable_column'],
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
                'fieldName' => 'nonSelectableContent',
                'selectable' => false,
            ],
        );
        $metadata->mapField(
            ['fieldName' => 'selectableContent'],
        );

        return $metadata;
    }
}
