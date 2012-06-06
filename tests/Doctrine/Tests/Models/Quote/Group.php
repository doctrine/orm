<?php

namespace Doctrine\Tests\Models\Quote;

/**
 * @Entity
 * @Table(name="`quote-group`")
 */
class Group
{

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer", name="`group-id`")
     */
    public $id;

    /**
     * @Column(name="`group-name`")
     */
    public $name;

    /**
     * @var Group
     *
     * @ManyToOne(targetEntity="Group", cascade={"persist"})
     * @JoinColumn(name="`parent-id`", referencedColumnName="`group-id`")
     */
    public $parent;

    /**
     * @ManyToMany(targetEntity="User", mappedBy="groups")
     */
    public $users;

    public function __construct($name = null, Group $parent =  null)
    {
        $this->name     = $name;
        $this->parent   = $parent;
    }
}