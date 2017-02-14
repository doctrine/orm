<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vct_inversed_onetomany_compositeid")
 */
class InversedOneToManyCompositeIdEntity
{
    /**
     * @ORM\Column(type="rot13")
     * @ORM\Id
     */
    public $id1;

    /**
     * @ORM\Column(type="rot13")
     * @ORM\Id
     */
    public $id2;

    /**
     * @ORM\Column(type="string", name="some_property")
     */
    public $someProperty;

    /**
     * @ORM\OneToMany(targetEntity="OwningManyToOneCompositeIdEntity", mappedBy="associatedEntity")
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
