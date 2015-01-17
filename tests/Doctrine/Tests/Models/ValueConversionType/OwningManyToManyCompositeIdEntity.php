<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="vct_owning_manytomany_compositeid")
 */
class OwningManyToManyCompositeIdEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id3;

    /**
     * @ManyToMany(targetEntity="InversedManyToManyCompositeIdEntity", inversedBy="associatedEntities")
     * @JoinTable(
     *     name="vct_xref_manytomany_compositeid",
     *     joinColumns={@JoinColumn(name="owning_id", referencedColumnName="id3")},
     *     inverseJoinColumns={
     *         @JoinColumn(name="inversed_id1", referencedColumnName="id1"),
     *         @JoinColumn(name="inversed_id2", referencedColumnName="id2")
     *     }
     * )
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
