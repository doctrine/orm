<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Tweet\User;
use Doctrine\Tests\Models\Tweet\Tweet;

class GH7864Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        $this->useModelSet('tweet');

        parent::setup();
    }

    public function testExtraLazyRemoveElement()
    {
        $user = new User();
        $user->name = "test";

        $tweet1 = new Tweet();
        $tweet1->content = "Hello World!";
        $user->addTweet($tweet1);

        $tweet2 = new Tweet();
        $tweet2->content = "Goodbye, and thanks for all the fish";
        $user->addTweet($tweet2);

        $this->_em->persist($user);
        $this->_em->persist($tweet1);
        $this->_em->persist($tweet2);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(User::class, $user->id);
        $tweet = $this->_em->find(Tweet::class, $tweet1->id);

        $user->tweets->removeElement($tweet);

        $tweets = $user->tweets->map(function (Tweet $tweet) { return $tweet->content; });

        $this->assertEquals(['Goodbye, and thanks for all the fish'], array_values($tweets->toArray()));
    }
}
