<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Tweet;

/**
 * @Entity
 * @Table(name="tweet_tweet")
 */
class Tweet
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
    public $content;

    /**
     * @var User
     * @ManyToOne(targetEntity="User", inversedBy="tweets")
     */
    public $author;

    public function setAuthor(User $user): void
    {
        $this->author = $user;
    }
}
