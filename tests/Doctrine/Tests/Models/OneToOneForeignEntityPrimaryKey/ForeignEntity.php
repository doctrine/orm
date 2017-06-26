<?php

namespace Doctrine\Tests\Models\OneToOneForeignEntityPrimaryKey;

/**
 * @Entity()
 * @Table("foreign_entity")
 */
class ForeignEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @OneToOne(targetEntity="Entity", inversedBy="foreignEntity")
     * @var Entity
     */
    private $entity;

    /**
     * @param Entity $entity
     *
     * @return void
     */
    public function setEntity(Entity $entity)
    {
        $this->entity = $entity;
        if ($entity->getForeignEntity() !== $this) {
            $entity->setForeignEntity($this);
        }
    }

    /**
     * @return Entity
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
