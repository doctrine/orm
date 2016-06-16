<?php

namespace Doctrine\Tests\Models\DDC5876;

/**
 * @Entity
 */
class DCC5876RootEntity
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
     * @OneToMany(targetEntity="DCC5876ChildEntity", mappedBy="rootEntity")
     * @OrderBy({"childRelationEntities" = "ASC"})
     */
    private $childEntities;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->childEntities = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add childEntity
     *
     * @param \Doctrine\Tests\Models\DDC5876\DCC5876ChildEntity $childEntity
     *
     * @return DCC5876RootEntity
     */
    public function addChildEntity(\Doctrine\Tests\Models\DDC5876\DCC5876ChildEntity $childEntity)
    {
        $this->childEntities[] = $childEntity;

        return $this;
    }

    /**
     * Remove childEntity
     *
     * @param \Doctrine\Tests\Models\DDC5876\DCC5876ChildEntity $childEntity
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeChildEntity(\Doctrine\Tests\Models\DDC5876\DCC5876ChildEntity $childEntity)
    {
        return $this->childEntities->removeElement($childEntity);
    }

    /**
     * Get childEntities
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildEntities()
    {
        return $this->childEntities;
    }
}
