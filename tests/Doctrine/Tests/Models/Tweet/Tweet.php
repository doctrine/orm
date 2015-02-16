<?php

namespace Doctrine\Tests\Models\Tweet;

/**
 * @Entity
 * @Table(name="tweet_tweet")
 */
class Tweet
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(type="string", length=140)
     */
    public $content = '';

    /**
     * @ManyToOne(targetEntity="User", inversedBy="tweets", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    public $author;

    /**
     * @param User $author
     */
    public function setAuthor(User $author)
    {
        $this->author = $author;
    }
}
