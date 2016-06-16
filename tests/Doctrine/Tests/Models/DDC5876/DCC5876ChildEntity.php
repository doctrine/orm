<?php

namespace Doctrine\Tests\Models\DDC5876;

/**
 * @Entity
 */
class DCC5876ChildEntity
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
     * @ManyToOne(targetEntity="DCC5876RootEntity", inversedBy="childEntities")
     * @JoinColumn(referencedColumnName="id")
     */
    private $rootEntity;

    /**
     * @OneToMany(targetEntity="DCC5876ChildRelationEntity", mappedBy="childEntity")
     * @JoinColumn(referencedColumnName="id")
     */
    private $childRelationEntities;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->childRelationEntities = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set rootEntity
     *
     * @param \Doctrine\Tests\Models\DDC5876\DCC5876RootEntity $rootEntity
     *
     * @return DCC5876ChildEntity
     */
    public function setRootEntity(\Doctrine\Tests\Models\DDC5876\DCC5876RootEntity $rootEntity = null)
    {
        $this->rootEntity = $rootEntity;

        return $this;
    }

    /**
     * Get rootEntity
     *
     * @return \Doctrine\Tests\Models\DDC5876\DCC5876RootEntity
     */
    public function getRootEntity()
    {
        return $this->rootEntity;
    }

    /**
     * Add childRelationEntity
     *
     * @param \Doctrine\Tests\Models\DDC5876\DCC5876ChildRelationEntity $childRelationEntity
     *
     * @return DCC5876ChildEntity
     */
    public function addChildRelationEntity(\Doctrine\Tests\Models\DDC5876\DCC5876ChildRelationEntity $childRelationEntity)
    {
        $this->childRelationEntities[] = $childRelationEntity;

        return $this;
    }

    /**
     * Remove childRelationEntity
     *
     * @param \Doctrine\Tests\Models\DDC5876\DCC5876ChildRelationEntity $childRelationEntity
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeChildRelationEntity(\Doctrine\Tests\Models\DDC5876\DCC5876ChildRelationEntity $childRelationEntity)
    {
        return $this->childRelationEntities->removeElement($childRelationEntity);
    }

    /**
     * Get childRelationEntities
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildRelationEntities()
    {
        return $this->childRelationEntities;
    }
}
