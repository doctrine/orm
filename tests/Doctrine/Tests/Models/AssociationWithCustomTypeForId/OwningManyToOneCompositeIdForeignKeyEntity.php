<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

/**
 * @Entity
 * @Table(name="act_owning_manytoone_compositeid_foreignkey_entities")
 */
class OwningManyToOneCompositeIdForeignKeyEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="InversedOneToManyCompositeIdForeignKeyEntity", inversedBy="associatedEntities")
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

    public function setAssociatedEntity(InversedOneToManyCompositeIdForeignKeyEntity $entity)
    {
        $this->associatedEntity = $entity;
    }
}
