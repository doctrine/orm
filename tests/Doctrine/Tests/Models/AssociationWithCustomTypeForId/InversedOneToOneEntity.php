<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

/**
 * @Entity
 * @Table(name="act_inversed_onetoone_entities")
 */
class InversedOneToOneEntity
{
    /**
     * @Column(type="uuid")
     * @Id
     */
    private $id;

    /**
     * @Column(type="string", name="proxy_load_trigger")
     */
    private $proxyLoadTrigger;

    /**
     * @OneToOne(targetEntity="OwningOneToOneEntity", mappedBy="associatedEntity")
     */
    private $associatedEntity;

    public function __construct($id, $proxyLoadTrigger)
    {
        $this->id = (string)$id;
        $this->proxyLoadTrigger = (string)$proxyLoadTrigger;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProxyLoadTrigger()
    {
        return $this->proxyLoadTrigger;
    }

    public function getAssociatedEntity()
    {
        return $this->associatedEntity;
    }

    public function setAssociatedEntity(OwningOneToOneEntity $entity)
    {
        $this->associatedEntity = $entity;
        $entity->setAssociatedEntity($this);
    }
}
