<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

/**
 * @Entity
 * @Table(name="act_owning_onetoone_entities")
 */
class OwningOneToOneEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id;

    /**
     * @OneToOne(targetEntity="InversedOneToOneEntity", inversedBy="associatedEntity")
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

    public function setAssociatedEntity(InversedOneToOneEntity $entity)
    {
        $this->associatedEntity = $entity;
    }
}
