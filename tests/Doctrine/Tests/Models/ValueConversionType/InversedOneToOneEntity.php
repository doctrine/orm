<?php

namespace Doctrine\Tests\Models\ValueConversionType;

/**
 * @Entity
 * @Table(name="vct_inversed_onetoone")
 */
class InversedOneToOneEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id1;

    /**
     * @Column(type="string", name="some_property")
     */
    public $someProperty;

    /**
     * @OneToOne(targetEntity="OwningOneToOneEntity", mappedBy="associatedEntity")
     */
    public $associatedEntity;
}
