<?php

namespace Doctrine\Tests\Models\Tweet;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tweet_tweet")
 */
class Tweet
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
    public $content;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="tweets")
     */
    public $author;

    public function setAuthor(User $user)
    {
        $this->author = $user;
    }
}
