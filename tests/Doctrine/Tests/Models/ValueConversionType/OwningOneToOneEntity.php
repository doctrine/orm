<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vct_owning_onetoone")
 */
class OwningOneToOneEntity
{
    /**
     * @ORM\Column(type="rot13")
     * @ORM\Id
     */
    public $id2;

    /**
     * @ORM\OneToOne(targetEntity="InversedOneToOneEntity", inversedBy="associatedEntity")
     * @ORM\JoinColumn(name="associated_id", referencedColumnName="id1")
     */
    public $associatedEntity;
}
