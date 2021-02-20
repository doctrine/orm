<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Forum;

use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="forum_categories")
 */
class ForumCategory
{
    /**
     * @var int
     * @Column(type="integer")
     * @Id
     */
    private $id;

    /**
     * @var int
     * @Column(type="integer")
     */
    public $position;

    /**
     * @var string
     * @Column(type="string", length=255)
     */
    public $name;

    /**
     * @psalm-var Collection<int, ForumBoard>
     * @OneToMany(targetEntity="ForumBoard", mappedBy="category")
     */
    public $boards;

    public function getId()
    {
        return $this->id;
    }
}
