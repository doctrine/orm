<?php

namespace Doctrine\Tests\Models\Forum;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="forum_categories")
 */
class ForumCategory
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    private $id;
    /**
     * @ORM\Column(type="integer")
     */
    public $position;
    /**
     * @ORM\Column(type="string", length=255)
     */
    public $name;
    /**
     * @ORM\OneToMany(targetEntity="ForumBoard", mappedBy="category")
     */
    public $boards;

    public function getId() {
        return $this->id;
    }
}
