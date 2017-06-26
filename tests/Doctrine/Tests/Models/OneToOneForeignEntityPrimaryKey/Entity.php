<?php

namespace Doctrine\Tests\Models\OneToOneForeignEntityPrimaryKey;

/**
 * @Entity
 * @Table("entity")
 */
class Entity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     * @var integer
     */
    private $id;

    /**
     * @OneToOne(targetEntity="ForeignEntity", mappedBy="entity", cascade={"persist"})
     * @var ForeignEntity|null
     */
    private $foreignEntity;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ForeignEntity|null
     */
    public function getForeignEntity()
    {
        return $this->foreignEntity;
    }

    /**
     * @param ForeignEntity $foreignEntity
     *
     * @return void
     */
    public function setForeignEntity(ForeignEntity $foreignEntity)
    {
        $this->foreignEntity = $foreignEntity;

        if ($foreignEntity->getEntity() !== $this) {
            $foreignEntity->setEntity($this);
        }
    }
}
