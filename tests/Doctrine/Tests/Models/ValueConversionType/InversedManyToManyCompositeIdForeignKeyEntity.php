<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vct_inversed_manytomany_compositeid_foreignkey")
 */
class InversedManyToManyCompositeIdForeignKeyEntity
{
    /**
     * @ORM\Column(type="rot13")
     * @ORM\Id
     */
    public $id1;

    /**
     * @ORM\ManyToOne(targetEntity="AuxiliaryEntity")
     * @ORM\JoinColumn(name="foreign_id", referencedColumnName="id4")
     * @ORM\Id
     */
    public $foreignEntity;

    /**
     * @ORM\ManyToMany(targetEntity="OwningManyToManyCompositeIdForeignKeyEntity", mappedBy="associatedEntities")
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
