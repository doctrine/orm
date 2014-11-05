<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="act_inversed_onetomany_compositeid_entities")
 */
class InversedOneToManyCompositeIdEntity
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
     * @OneToMany(targetEntity="OwningManyToOneCompositeIdEntity", mappedBy="associatedEntity")
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

    public function addAssociatedEntity(OwningManyToOneCompositeIdEntity $entity)
    {
        $this->associatedEntities->add($entity);
        $entity->setAssociatedEntity($this);
    }
}
