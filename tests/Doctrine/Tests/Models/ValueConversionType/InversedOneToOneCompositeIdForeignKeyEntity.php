<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

/**
 * @Entity
 * @Table(name="vct_inversed_onetoone_compositeid_foreignkey")
 */
class InversedOneToOneCompositeIdForeignKeyEntity
{
    /**
     * @var string
     * @Column(type="rot13")
     * @Id
     */
    public $id1;

    /**
     * @var AuxiliaryEntity
     * @ManyToOne(targetEntity="AuxiliaryEntity")
     * @JoinColumn(name="foreign_id", referencedColumnName="id4")
     * @Id
     */
    public $foreignEntity;

    /**
     * @var string
     * @Column(type="string", name="some_property")
     */
    public $someProperty;

    /**
     * @var OwningOneToOneCompositeIdForeignKeyEntity
     * @OneToOne(targetEntity="OwningOneToOneCompositeIdForeignKeyEntity", mappedBy="associatedEntity")
     */
    public $associatedEntity;
}
