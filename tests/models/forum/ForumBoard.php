<?php

#namespace Doctrine\Tests\Models\Forum;

/**
 * Represents a board in a forum.
 *
 * @author robo
 * @DoctrineEntity
 */
class ForumBoard
{
    /**
     * @DoctrineId
     * @DoctrineColumn(type="integer")
     */
    public $id;
    /**
     * @DoctrineColumn(type="integer")
     */
    public $position;
    /**
     * @DoctrineManyToOne(targetEntity="ForumCategory", joinColumns={"category_id" = "id"})
     */
    public $category;
}
