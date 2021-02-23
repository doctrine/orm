<?php

namespace Doctrine\Tests\Models\DDC5876;

/**
 * @Entity
 */
class DCC5876ChildRelationEntity
{
    const CLASSNAME = __CLASS__;
    
    /**
     * @var int
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="DCC5876ChildEntity", inversedBy="childRelationEntities")
     * @JoinColumn(referencedColumnName="id")
     */
    private $childEntity;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set childEntity
     *
     * @param \Doctrine\Tests\Models\DDC5876\DCC5876ChildEntity $childEntity
     *
     * @return DCC5876ChildRelationEntity
     */
    public function setChildEntity(\Doctrine\Tests\Models\DDC5876\DCC5876ChildEntity $childEntity = null)
    {
        $this->childEntity = $childEntity;

        return $this;
    }

    /**
     * Get childEntity
     *
     * @return \Doctrine\Tests\Models\DDC5876\DCC5876ChildEntity
     */
    public function getChildEntity()
    {
        return $this->childEntity;
    }
}
