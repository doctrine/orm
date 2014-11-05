<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="act_inversed_manytomany_compositeid_entities")
 */
class InversedManyToManyCompositeIdEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id1;

    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id2;

    /**
     * @ManyToMany(targetEntity="OwningManyToManyCompositeIdEntity", mappedBy="associatedEntities")
     */
    private $associatedEntities;

    public function __construct($id1, $id2)
    {
        $this->id1 = (string)$id1;
        $this->id2 = (string)$id2;
        $this->associatedEntities = new ArrayCollection();
    }

    public function getId1()
    {
        return $this->id1;
    }

    public function getId2()
    {
        return $this->id2;
    }

    public function getAssociatedEntities()
    {
        return $this->associatedEntities->toArray();
    }

    public function addAssociatedEntity(OwningManyToManyCompositeIdEntity $entity)
    {
        if (!$this->associatedEntities->contains($entity)) {
            $this->associatedEntities->add($entity);
            $entity->addAssociatedEntity($this);
        }
    }

    public function removeAssociatedEntity(OwningManyToManyCompositeIdEntity $entity)
    {
        if ($this->associatedEntities->contains($entity)) {
            $this->associatedEntities->removeElement($entity);
            $entity->removeAssociatedEntity($this);
        }
    }
}
