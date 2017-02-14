<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vct_inversed_onetoone")
 */
class InversedOneToOneEntity
{
    /**
     * @ORM\Column(type="rot13")
     * @ORM\Id
     */
    public $id1;

    /**
     * @ORM\Column(type="string", name="some_property")
     */
    public $someProperty;

    /**
     * @ORM\OneToOne(targetEntity="OwningOneToOneEntity", mappedBy="associatedEntity")
     */
    public $associatedEntity;
}
