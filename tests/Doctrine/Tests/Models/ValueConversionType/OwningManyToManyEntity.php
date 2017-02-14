<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vct_owning_manytomany")
 */
class OwningManyToManyEntity
{
    /**
     * @ORM\Column(type="rot13")
     * @ORM\Id
     */
    public $id2;

    /**
     * @ORM\ManyToMany(targetEntity="InversedManyToManyEntity", inversedBy="associatedEntities")
     * @ORM\JoinTable(
     *     name="vct_xref_manytomany",
     *     joinColumns={@ORM\JoinColumn(name="owning_id", referencedColumnName="id2")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="inversed_id", referencedColumnName="id1")}
     * )
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
