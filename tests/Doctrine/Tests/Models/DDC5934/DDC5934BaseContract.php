<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC5934;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;

/** @Entity */
#[Entity]
class DDC5934BaseContract
{
    /**
     * @var int
     * @Id()
     * @Column(name="id", type="integer")
     * @GeneratedValue()
     */
    #[Id]
    #[Column]
    #[GeneratedValue]
    public $id;

    /**
     * @psalm-var Collection<int, DDC5934Member>
     * @ManyToMany(targetEntity="DDC5934Member", fetch="LAZY", inversedBy="contracts")
     */
    #[ManyToMany(targetEntity: DDC5934Member::class, fetch: 'LAZY', inversedBy: 'contracts')]
    public $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->mapField([
            'id'         => true,
            'fieldName'  => 'id',
            'type'       => 'integer',
            'columnName' => 'id',
        ]);

        $metadata->mapManyToMany([
            'fieldName'    => 'members',
            'targetEntity' => 'DDC5934Member',
        ]);

        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
    }
}
