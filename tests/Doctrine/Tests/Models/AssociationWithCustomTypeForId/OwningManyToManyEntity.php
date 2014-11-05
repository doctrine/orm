<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="act_owning_manytomany_entities")
 */
class OwningManyToManyEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id;

    /**
     * @ManyToMany(targetEntity="InversedManyToManyEntity", inversedBy="associatedEntities")
     * @JoinTable(
     *     name="act_xref_manytomany",
     *     joinColumns={@JoinColumn(name="owning_id", referencedColumnName="id")},
     *     inverseJoinColumns={@JoinColumn(name="inversed_id", referencedColumnName="id")}
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

    public function addAssociatedEntity(InversedManyToManyEntity $entity)
    {
        if (!$this->associatedEntities->contains($entity)) {
            $this->associatedEntities->add($entity);
            $entity->addAssociatedEntity($this);
        }
    }

    public function removeAssociatedEntity(InversedManyToManyEntity $entity)
    {
        if ($this->associatedEntities->contains($entity)) {
            $this->associatedEntities->removeElement($entity);
            $entity->removeAssociatedEntity($this);
        }
    }
}
