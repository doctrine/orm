<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="vct_inversed_onetomany_compositeid_foreignkey")
 */
class InversedOneToManyCompositeIdForeignKeyEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id1;

    /**
     * @ManyToOne(targetEntity="AuxiliaryEntity")
     * @JoinColumn(name="foreign_id", referencedColumnName="id4")
     * @Id
     */
    public $foreignEntity;

    /**
     * @Column(type="string", name="some_property")
     */
    public $someProperty;

    /**
     * @OneToMany(targetEntity="OwningManyToOneCompositeIdForeignKeyEntity", mappedBy="associatedEntity")
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
