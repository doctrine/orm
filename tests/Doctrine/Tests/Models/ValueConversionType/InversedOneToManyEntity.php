<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vct_inversed_onetomany")
 */
class InversedOneToManyEntity
{
    /**
     * @ORM\Column(type="rot13")
     * @ORM\Id
     */
    public $id1;

    /**
     * @ORM\OneToMany(targetEntity="OwningManyToOneEntity", mappedBy="associatedEntity")
     */
    public $associatedEntities;

    /**
     * @ORM\Column(type="string", name="some_property")
     */
    public $someProperty;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
