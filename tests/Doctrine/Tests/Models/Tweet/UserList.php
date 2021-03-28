<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Tweet;

/**
 * @Entity
 * @Table(name="tweet_user_list")
 */
class UserList
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @var string
     * @Column(type="string")
     */
    public $listName;

    /**
     * @var User
     * @ManyToOne(targetEntity="User", inversedBy="userLists")
     */
    public $owner;
}
