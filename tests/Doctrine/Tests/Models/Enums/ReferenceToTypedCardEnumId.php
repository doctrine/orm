<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/** @Entity */
#[Entity]
class ReferenceToTypedCardEnumId
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
     * @ORM\ManyToOne(targetEntity="TypedCardEnumId")
     * @ORM\JoinColumn(name="typed_card_id", referencedColumnName="suit", nullable=false)
     */
    #[ManyToOne(targetEntity: TypedCardEnumId::class)]
    #[JoinColumn(nullable: false)]
    public TypedCardEnumId $typedCard;

}
