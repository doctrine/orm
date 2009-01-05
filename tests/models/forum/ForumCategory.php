<?php

#namespace Doctrine\Tests\Models\Forum;

/**
 * @DoctrineEntity
 */
class ForumCategory
{
    /**
     * @DoctrineColumn(type="integer")
     * @DoctrineId
     */
    private $id;
    /**
     * @DoctrineColumn(type="integer")
     */
    public $position;
    /**
     * @DoctrineColumn(type="string", length=255)
     */
    public $name;
    /**
     * @DoctrineOneToMany(targetEntity="ForumBoard", mappedBy="category")
     */
    public $boards;

    public function getId() {
        return $this->id;
    }
}
