<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vct_inversed_onetomany_compositeid_foreignkey")
 */
class InversedOneToManyCompositeIdForeignKeyEntity
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
     * @ORM\Column(type="string", name="some_property")
     */
    public $someProperty;

    /**
     * @ORM\OneToMany(targetEntity="OwningManyToOneCompositeIdForeignKeyEntity", mappedBy="associatedEntity")
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
