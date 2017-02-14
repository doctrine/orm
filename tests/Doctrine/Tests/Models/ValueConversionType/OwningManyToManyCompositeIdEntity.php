<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vct_owning_manytomany_compositeid")
 */
class OwningManyToManyCompositeIdEntity
{
    /**
     * @ORM\Column(type="rot13")
     * @ORM\Id
     */
    public $id3;

    /**
     * @ORM\ManyToMany(targetEntity="InversedManyToManyCompositeIdEntity", inversedBy="associatedEntities")
     * @ORM\JoinTable(
     *     name="vct_xref_manytomany_compositeid",
     *     joinColumns={@ORM\JoinColumn(name="owning_id", referencedColumnName="id3")},
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="inversed_id1", referencedColumnName="id1"),
     *         @ORM\JoinColumn(name="inversed_id2", referencedColumnName="id2")
     *     }
     * )
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
