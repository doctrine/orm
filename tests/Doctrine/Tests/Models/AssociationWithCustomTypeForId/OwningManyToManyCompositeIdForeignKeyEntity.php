<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="act_owning_manytomany_compositeid_foreignkey_entities")
 */
class OwningManyToManyCompositeIdForeignKeyEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id;

    /**
     * @ManyToMany(targetEntity="InversedManyToManyCompositeIdForeignKeyEntity", inversedBy="associatedEntities")
     * @JoinTable(
     *     name="act_xref_manytomany_compositeid_foreignkey",
     *     joinColumns={@JoinColumn(name="owning_id", referencedColumnName="id")},
     *     inverseJoinColumns={
     *         @JoinColumn(name="associated_id", referencedColumnName="id"),
     *         @JoinColumn(name="associated_foreign_id", referencedColumnName="foreign_id")
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

    public function addAssociatedEntity(InversedManyToManyCompositeIdForeignKeyEntity $entity)
    {
        if (!$this->associatedEntities->contains($entity)) {
            $this->associatedEntities->add($entity);
            $entity->addAssociatedEntity($this);
        }
    }

    public function removeAssociatedEntity(InversedManyToManyCompositeIdForeignKeyEntity $entity)
    {
        if ($this->associatedEntities->contains($entity)) {
            $this->associatedEntities->removeElement($entity);
            $entity->removeAssociatedEntity($this);
        }
    }
}
