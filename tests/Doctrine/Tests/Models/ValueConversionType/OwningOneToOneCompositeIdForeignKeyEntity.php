<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="vct_owning_onetoone_compositeid_foreignkey",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="associated_entity_uniq", columns={"associated_id", "associated_foreign_id"})
 *     }
 * )
 */
class OwningOneToOneCompositeIdForeignKeyEntity
{
    /**
     * @ORM\Column(type="rot13")
     * @ORM\Id
     */
    public $id2;

    /**
     * @ORM\OneToOne(targetEntity="InversedOneToOneCompositeIdForeignKeyEntity", inversedBy="associatedEntity")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="associated_id", referencedColumnName="id1"),
     *     @ORM\JoinColumn(name="associated_foreign_id", referencedColumnName="foreign_id")
     * })
     */
    public $associatedEntity;
}
