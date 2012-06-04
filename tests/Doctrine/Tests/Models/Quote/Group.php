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
     * @ManyToMany(targetEntity="User", mappedBy="groups")
     */
    public $users;

}