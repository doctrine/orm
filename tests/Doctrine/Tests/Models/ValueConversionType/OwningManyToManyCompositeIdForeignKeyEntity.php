<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="vct_owning_manytomany_compositeid_foreignkey")
 */
class OwningManyToManyCompositeIdForeignKeyEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id;

    /**
     * @ManyToMany(targetEntity="InversedManyToManyCompositeIdForeignKeyEntity", inversedBy="associatedEntities")
     * @JoinTable(
     *     name="vct_xref_manytomany_compositeid_foreignkey",
     *     joinColumns={@JoinColumn(name="owning_id", referencedColumnName="id")},
     *     inverseJoinColumns={
     *         @JoinColumn(name="associated_id", referencedColumnName="id"),
     *         @JoinColumn(name="associated_foreign_id", referencedColumnName="foreign_id")
     *     }
     * )
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
