<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class Scale
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /** @var Unit[] */
    #[Column(type: 'simple_array', enumType: Unit::class)]
    public $supportedUnits;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->mapField(
            [
                'id' => true,
                'fieldName' => 'id',
                'type' => 'integer',
            ],
        );
        $metadata->mapField(
            [
                'fieldName' => 'supportedUnits',
                'type' => 'simple_array',
                'enumType' => Unit::class,
            ],
        );
    }
}
