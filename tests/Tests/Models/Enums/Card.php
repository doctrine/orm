<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/** @Entity */
#[Entity]
class Card
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /**
     * @Column(type="string", length=255, enumType=Suit::class)
     * @var Suit
     */
    #[Column(type: 'string', enumType: Suit::class)]
    public $suit;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->mapField(
            [
                'id' => true,
                'fieldName' => 'id',
                'type' => 'integer',
            ]
        );
        $metadata->mapField(
            [
                'fieldName' => 'suit',
                'type' => 'string',
                'enumType' => Suit::class,
            ]
        );
    }
}
