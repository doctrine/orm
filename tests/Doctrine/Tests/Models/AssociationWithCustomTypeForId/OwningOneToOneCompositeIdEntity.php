<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

/**
 * @Entity
 * @Table(name="act_owning_onetoone_compositeid_entities")
 */
class OwningOneToOneCompositeIdEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id;

    /**
     * @OneToOne(targetEntity="InversedOneToOneCompositeIdEntity", inversedBy="associatedEntity")
     * @JoinColumns({
     *     @JoinColumn(name="associated_id1", referencedColumnName="id1"),
     *     @JoinColumn(name="associated_id2", referencedColumnName="id2")
     * })
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

    public function setAssociatedEntity(InversedOneToOneCompositeIdEntity $entity)
    {
        $this->associatedEntity = $entity;
    }
}
