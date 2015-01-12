<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="vct_owning_manytomany")
 */
class OwningManyToManyEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id2;

    /**
     * @ManyToMany(targetEntity="InversedManyToManyEntity", inversedBy="associatedEntities")
     * @JoinTable(
     *     name="vct_xref_manytomany",
     *     joinColumns={@JoinColumn(name="owning_id", referencedColumnName="id2")},
     *     inverseJoinColumns={@JoinColumn(name="inversed_id", referencedColumnName="id1")}
     * )
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
