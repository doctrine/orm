<?php

namespace Doctrine\Tests\Models\Forum;

/**
 * Represents a board in a forum.
 *
 * @author robo
 * @Entity
 * @Table(name="forum_boards")
 */
class ForumBoard
{
    /**
     * @Id
     * @Column(type="integer")
     */
    public $id;
    /**
     * @Column(type="integer")
     */
    public $position;
    /**
     * @ManyToOne(targetEntity="ForumCategory", inversedBy="boards")
     * @JoinColumn(name="category_id", referencedColumnName="id")
     */
    public $category;
}
