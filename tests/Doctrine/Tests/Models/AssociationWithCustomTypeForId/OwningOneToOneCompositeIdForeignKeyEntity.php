<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

/**
 * @Entity
 * @Table(
 *     name="act_owning_onetoone_compositeid_foreignkey_entities",
 *     uniqueConstraints={
 *         @UniqueConstraint(name="associated_entity_uniq", columns={"associated_id", "associated_foreign_id"})
 *     }
 * )
 */
class OwningOneToOneCompositeIdForeignKeyEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id;

    /**
     * @OneToOne(targetEntity="InversedOneToOneCompositeIdForeignKeyEntity", inversedBy="associatedEntity")
     * @JoinColumns({
     *     @JoinColumn(name="associated_id", referencedColumnName="id"),
     *     @JoinColumn(name="associated_foreign_id", referencedColumnName="foreign_id")
     * })
     */
    private $associatedEntity;

    public function __construct($id)
    {
        $this->id = (string)$id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAssociatedEntity()
    {
        return $this->associatedEntity;
    }

    public function setAssociatedEntity(InversedOneToOneCompositeIdForeignKeyEntity $entity)
    {
        $this->associatedEntity = $entity;
    }
}
