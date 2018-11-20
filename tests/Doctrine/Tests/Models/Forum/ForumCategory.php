<?php

namespace Doctrine\Tests\Models\Forum;

/**
 * @Entity
 * @Table(name="forum_categories")
 */
class ForumCategory
{
    /**
     * @Column(type="integer")
     * @Id
     */
    private $id;
    /**
     * @Column(type="integer")
     */
    public $position;
    /**
     * @Column(type="string", length=255)
     */
    public $name;
    /**
     * @OneToMany(targetEntity="ForumBoard", mappedBy="category")
     */
    public $boards;

    public function getId() {
        return $this->id;
    }
}
