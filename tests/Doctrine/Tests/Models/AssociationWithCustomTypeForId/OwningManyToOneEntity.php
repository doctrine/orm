<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

/**
 * @Entity
 * @Table(name="act_owning_manytoone_entities")
 */
class OwningManyToOneEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="InversedOneToManyEntity", inversedBy="associatedEntities")
     * @JoinColumn(name="associated_id", referencedColumnName="id")
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

    public function setAssociatedEntity(InversedOneToManyEntity $entity)
    {
        $this->associatedEntity = $entity;
    }
}
