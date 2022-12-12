<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class CardWithNullable
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /** @var ?Suit */
    #[Column(type: 'string', nullable: true, enumType: Suit::class)]
    public $suit;

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
                'fieldName' => 'suit',
                'type' => 'string',
                'enumType' => Suit::class,
                'nullable' => true,
            ],
        );
    }
}
