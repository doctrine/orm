<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="act_inversed_onetomany_compositeid_foreignkey_entities")
 */
class InversedOneToManyCompositeIdForeignKeyEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="AuxiliaryEntity")
     * @JoinColumn(name="foreign_id", referencedColumnName="id")
     * @Id
     */
    private $foreignEntity;

    /**
     * @OneToMany(targetEntity="OwningManyToOneCompositeIdForeignKeyEntity", mappedBy="associatedEntity")
     */
    private $associatedEntities;

    public function __construct($id, AuxiliaryEntity $foreignEntity)
    {
        $this->id = (string)$id;
        $this->foreignEntity = $foreignEntity;
        $this->associatedEntities = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getForeignEntity()
    {
        return $this->foreignEntity;
    }

    public function getAssociatedEntities()
    {
        return $this->associatedEntities->toArray();
    }

    public function addAssociatedEntity(OwningManyToOneCompositeIdForeignKeyEntity $entity)
    {
        $this->associatedEntities->add($entity);
        $entity->setAssociatedEntity($this);
    }
}
