<?php

namespace Doctrine\Tests\Models\Tweet;

/**
 * @Entity
 * @Table(name="tweet_tweet")
 */
class Tweet
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
    public $content;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="tweets")
     */
    public $author;

    public function setAuthor(User $user)
    {
        $this->author = $user;
    }
}
