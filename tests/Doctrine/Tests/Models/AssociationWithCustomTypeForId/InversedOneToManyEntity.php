<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="act_inversed_onetomany_entities")
 */
class InversedOneToManyEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id;

    /**
     * @OneToMany(targetEntity="OwningManyToOneEntity", mappedBy="associatedEntity")
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

    public function addAssociatedEntity(OwningManyToOneEntity $entity)
    {
        $this->associatedEntities->add($entity);
        $entity->setAssociatedEntity($this);
    }
}
