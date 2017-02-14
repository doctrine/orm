<?php

namespace Doctrine\Tests\Models\Tweet;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tweet_user")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $name;

    /**
     * @ORM\OneToMany(targetEntity="Tweet", mappedBy="author", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    public $tweets;

    /**
     * @ORM\OneToMany(targetEntity="UserList", mappedBy="owner", fetch="EXTRA_LAZY", orphanRemoval=true)
     */
    public $userLists;

    public function __construct()
    {
        $this->tweets    = new ArrayCollection();
        $this->userLists = new ArrayCollection();
    }

    public function addTweet(Tweet $tweet)
    {
        $tweet->setAuthor($this);
        $this->tweets->add($tweet);
    }

    public function addUserList(UserList $userList)
    {
        $userList->owner = $this;
        $this->userLists->add($userList);
    }
}
