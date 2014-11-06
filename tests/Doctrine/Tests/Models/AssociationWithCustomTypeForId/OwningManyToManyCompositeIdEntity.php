<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="act_owning_manytomany_compositeid_entities")
 */
class OwningManyToManyCompositeIdEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id;

    /**
     * @ManyToMany(targetEntity="InversedManyToManyCompositeIdEntity", inversedBy="associatedEntities")
     * @JoinTable(
     *     name="act_xref_manytomany_compositeid",
     *     joinColumns={@JoinColumn(name="owning_id", referencedColumnName="id")},
     *     inverseJoinColumns={
     *         @JoinColumn(name="inversed_id1", referencedColumnName="id1"),
     *         @JoinColumn(name="inversed_id2", referencedColumnName="id2")
     *     }
     * )
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

    public function addAssociatedEntity(InversedManyToManyCompositeIdEntity $entity)
    {
        if (!$this->associatedEntities->contains($entity)) {
            $this->associatedEntities->add($entity);
            $entity->addAssociatedEntity($this);
        }
    }

    public function removeAssociatedEntity(InversedManyToManyCompositeIdEntity $entity)
    {
        if ($this->associatedEntities->contains($entity)) {
            $this->associatedEntities->removeElement($entity);
            $entity->removeAssociatedEntity($this);
        }
    }
}
