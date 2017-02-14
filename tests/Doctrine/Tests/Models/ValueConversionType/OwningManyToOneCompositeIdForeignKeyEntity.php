<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vct_owning_manytoone_compositeid_foreignkey")
 */
class OwningManyToOneCompositeIdForeignKeyEntity
{
    /**
     * @ORM\Column(type="rot13")
     * @ORM\Id
     */
    public $id2;

    /**
     * @ORM\ManyToOne(targetEntity="InversedOneToManyCompositeIdForeignKeyEntity", inversedBy="associatedEntities")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="associated_id", referencedColumnName="id1"),
     *     @ORM\JoinColumn(name="associated_foreign_id", referencedColumnName="foreign_id")
     * })
     */
    public $associatedEntity;
}
