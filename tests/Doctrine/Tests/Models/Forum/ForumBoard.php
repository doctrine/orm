<?php

namespace Doctrine\Tests\Models\Forum;

/**
 * Represents a board in a forum.
 *
 * @author robo
 * @DoctrineEntity
 * @DoctrineTable(name="forum_boards")
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
     * @DoctrineManyToOne(targetEntity="ForumCategory")
     * @DoctrineJoinColumn(name="category_id", referencedColumnName="id")
     */
    public $category;
}
