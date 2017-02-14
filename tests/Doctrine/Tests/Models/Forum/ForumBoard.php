<?php

namespace Doctrine\Tests\Models\Forum;

use Doctrine\ORM\Annotation as ORM;

/**
 * Represents a board in a forum.
 *
 * @author robo
 * @ORM\Entity
 * @ORM\Table(name="forum_boards")
 */
class ForumBoard
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    public $id;
    /**
     * @ORM\Column(type="integer")
     */
    public $position;
    /**
     * @ORM\ManyToOne(targetEntity="ForumCategory", inversedBy="boards")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    public $category;
}
