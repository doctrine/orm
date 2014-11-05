<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="act_inversed_manytomany_entities")
 */
class InversedManyToManyEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id;

    /**
     * @ManyToMany(targetEntity="OwningManyToManyEntity", mappedBy="associatedEntities")
     */
    private $associatedEntities;

    public function __construct($id)
    {
        $this->id = (string)$id;
        $this->associatedEntities = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAssociatedEntities()
    {
        return $this->associatedEntities->toArray();
    }

    public function addAssociatedEntity(OwningManyToManyEntity $entity)
    {
        if (!$this->associatedEntities->contains($entity)) {
            $this->associatedEntities->add($entity);
            $entity->addAssociatedEntity($this);
        }
    }

    public function removeAssociatedEntity(OwningManyToManyEntity $entity)
    {
        if ($this->associatedEntities->contains($entity)) {
            $this->associatedEntities->removeElement($entity);
            $entity->removeAssociatedEntity($this);
        }
    }
}
