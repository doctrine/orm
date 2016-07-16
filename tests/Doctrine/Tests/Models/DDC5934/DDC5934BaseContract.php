<?php

namespace Doctrine\Tests\Models\DDC5934;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;

/**
 * @Entity
 */
class DDC5934BaseContract
{
    /**
     * @Id()
     * @Column(name="id", type="integer")
     * @GeneratedValue()
     */
    public $id;

    /**
     * @var ArrayCollection
     *
     * @ManyToMany(targetEntity="DDC5934Member", fetch="LAZY", inversedBy="contracts")
     */
    public $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public static function loadMetadata(ClassMetadata $metadata)
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
