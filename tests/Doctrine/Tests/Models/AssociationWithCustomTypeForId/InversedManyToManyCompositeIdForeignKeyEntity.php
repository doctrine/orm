<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="act_inversed_manytomany_compositeid_foreignkey_entities")
 */
class InversedManyToManyCompositeIdForeignKeyEntity
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
     * @ManyToMany(targetEntity="OwningManyToManyCompositeIdForeignKeyEntity", mappedBy="associatedEntities")
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

    public function addAssociatedEntity(OwningManyToManyCompositeIdForeignKeyEntity $entity)
    {
        if (!$this->associatedEntities->contains($entity)) {
            $this->associatedEntities->add($entity);
            $entity->addAssociatedEntity($this);
        }
    }

    public function removeAssociatedEntity(OwningManyToManyCompositeIdForeignKeyEntity $entity)
    {
        if ($this->associatedEntities->contains($entity)) {
            $this->associatedEntities->removeElement($entity);
            $entity->removeAssociatedEntity($this);
        }
    }
}
