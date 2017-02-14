<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vct_owning_onetoone_compositeid")
 */
class OwningOneToOneCompositeIdEntity
{
    /**
     * @ORM\Column(type="rot13")
     * @ORM\Id
     */
    public $id3;

    /**
     * @ORM\OneToOne(targetEntity="InversedOneToOneCompositeIdEntity", inversedBy="associatedEntity")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="associated_id1", referencedColumnName="id1"),
     *     @ORM\JoinColumn(name="associated_id2", referencedColumnName="id2")
     * })
     */
    public $associatedEntity;
}
