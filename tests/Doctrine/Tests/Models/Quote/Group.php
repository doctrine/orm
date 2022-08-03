<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Quote;

use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="`quote-group`")
 */
class Group
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(type="integer", name="`group-id`")
     */
    public $id;

    /**
     * @var string|null
     * @Column(name="`group-name`")
     */
    public $name;

    /**
     * @var Group|null
     * @ManyToOne(targetEntity="Group", cascade={"persist"})
     * @JoinColumn(name="`parent-id`", referencedColumnName="`group-id`")
     */
    public $parent;

    /**
     * @psalm-var Collection<int, User>
     * @ManyToMany(targetEntity="User", mappedBy="groups")
     */
    public $users;

    public function __construct(?string $name = null, ?Group $parent = null)
    {
        $this->name   = $name;
        $this->parent = $parent;
    }
}
