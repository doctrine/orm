<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vct_inversed_onetomany_extralazy")
 */
class InversedOneToManyExtraLazyEntity
{
    /**
     * @ORM\Column(type="rot13")
     * @ORM\Id
     */
    public $id1;

    /**
     * @ORM\OneToMany(
     *     targetEntity="OwningManyToOneExtraLazyEntity",
     *     mappedBy="associatedEntity",
     *     fetch="EXTRA_LAZY",
     *     indexBy="id2"
     * )
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
