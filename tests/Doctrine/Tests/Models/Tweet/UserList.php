<?php

namespace Doctrine\Tests\Models\Tweet;

/**
 * @Entity
 * @Table(name="tweet_user_list")
 */
class UserList
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $listName;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="userLists")
     */
    public $owner;
}
