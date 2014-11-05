<?php

namespace Doctrine\Tests\Models\AssociationWithCustomTypeForId;

/**
 * @Entity
 * @Table(name="act_inversed_onetoone_compositeid_entities")
 */
class InversedOneToOneCompositeIdEntity
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
     * @Column(type="string", name="proxy_load_trigger")
     */
    private $proxyLoadTrigger;

    /**
     * @OneToOne(targetEntity="OwningOneToOneCompositeIdEntity", mappedBy="associatedEntity")
     */
    private $associatedEntity;

    public function __construct($id1, $id2, $proxyLoadTrigger)
    {
        $this->id1 = (string)$id1;
        $this->id2 = (string)$id2;
        $this->proxyLoadTrigger = (string)$proxyLoadTrigger;
    }

    public function getId1()
    {
        return $this->id1;
    }

    public function getId2()
    {
        return $this->id2;
    }

    public function getProxyLoadTrigger()
    {
        return $this->proxyLoadTrigger;
    }

    public function getAssociatedEntity()
    {
        return $this->associatedEntity;
    }

    public function setAssociatedEntity(OwningOneToOneCompositeIdEntity $entity)
    {
        $this->associatedEntity = $entity;
        $entity->setAssociatedEntity($this);
    }
}
