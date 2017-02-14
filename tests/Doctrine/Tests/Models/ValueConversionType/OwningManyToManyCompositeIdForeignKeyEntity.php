<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vct_owning_manytomany_compositeid_foreignkey")
 */
class OwningManyToManyCompositeIdForeignKeyEntity
{
    /**
     * @ORM\Column(type="rot13")
     * @ORM\Id
     */
    public $id2;

    /**
     * @ORM\ManyToMany(targetEntity="InversedManyToManyCompositeIdForeignKeyEntity", inversedBy="associatedEntities")
     * @ORM\JoinTable(
     *     name="vct_xref_manytomany_compositeid_foreignkey",
     *     joinColumns={@ORM\JoinColumn(name="owning_id", referencedColumnName="id2")},
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="associated_id", referencedColumnName="id1"),
     *         @ORM\JoinColumn(name="associated_foreign_id", referencedColumnName="foreign_id")
     *     }
     * )
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
