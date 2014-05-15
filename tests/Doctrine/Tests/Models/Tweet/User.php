<?php

namespace Doctrine\Tests\Models\Tweet;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="tweet_user")
 */
class User
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;

    /**
     * @OneToMany(targetEntity="Tweet", mappedBy="author", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    public $tweets;

    public function __construct()
    {
        $this->tweets = new ArrayCollection();
    }

    public function addTweet(Tweet $tweet)
    {
        $tweet->setAuthor($this);
        $this->tweets->add($tweet);
    }
}
