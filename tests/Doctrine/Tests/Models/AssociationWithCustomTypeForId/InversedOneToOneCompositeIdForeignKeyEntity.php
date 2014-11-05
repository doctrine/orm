<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

/**
 * @Entity
 * @Table(name="act_inversed_onetoone_compositeid_foreignkey_entities")
 */
class InversedOneToOneCompositeIdForeignKeyEntity
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
     * @Column(type="string", name="proxy_load_trigger")
     */
    private $proxyLoadTrigger;

    /**
     * @OneToOne(targetEntity="OwningOneToOneCompositeIdForeignKeyEntity", mappedBy="associatedEntity")
     */
    private $associatedEntity;

    public function __construct($id, AuxiliaryEntity $foreignEntity, $proxyLoadTrigger)
    {
        $this->id = (string)$id;
        $this->foreignEntity = $foreignEntity;
        $this->proxyLoadTrigger = (string)$proxyLoadTrigger;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getForeignEntity()
    {
        return $this->foreignEntity;
    }

    public function getProxyLoadTrigger()
    {
        return $this->proxyLoadTrigger;
    }

    public function getAssociatedEntity()
    {
        return $this->associatedEntity;
    }

    public function setAssociatedEntity(OwningOneToOneCompositeIdForeignKeyEntity $entity)
    {
        $this->associatedEntity = $entity;
        $entity->setAssociatedEntity($this);
    }
}
