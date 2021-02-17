<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="vct_inversed_onetomany")
 */
class InversedOneToManyEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id1;

    /**
     * @psalm-var Collection<int, OwningManyToOneEntity>
     * @OneToMany(targetEntity="OwningManyToOneEntity", mappedBy="associatedEntity")
     */
    public $associatedEntities;

    /**
     * @var string
     * @Column(type="string", name="some_property")
     */
    public $someProperty;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
