<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="vct_inversed_manytomany_compositeid_foreignkey")
 */
class InversedManyToManyCompositeIdForeignKeyEntity
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
     * @ManyToMany(targetEntity="OwningManyToManyCompositeIdForeignKeyEntity", mappedBy="associatedEntities")
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
