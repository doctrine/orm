<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

/**
 * @Entity
 * @Table(name="act_owning_manytoone_compositeid_entities")
 */
class OwningManyToOneCompositeIdEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="InversedOneToManyCompositeIdEntity", inversedBy="associatedEntities")
     * @JoinColumns({
     *     @JoinColumn(name="associated_id1", referencedColumnName="id1"),
     *     @JoinColumn(name="associated_id2", referencedColumnName="id2")
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

    public function setAssociatedEntity(InversedOneToManyCompositeIdEntity $entity)
    {
        $this->associatedEntity = $entity;
    }
}
