<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\Models\Tweet\Tweet;
use Doctrine\Tests\Models\Tweet\User;

/**
 * @group DDC-3343
 */
class DDC3343Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->useModelSet('tweet');

        parent::setUp();
    }

    public function testEntityNotDeletedWhenRemovedFromExtraLazyAssociation()
    {
        $user  = new User();
        $tweet = new Tweet();

        $user->name     = 'ocramius';
        $tweet->content = 'The cat is on the table';

        $user->addTweet($tweet);

        $this->_em->persist($user);
        $this->_em->persist($tweet);
        $this->_em->flush();
        $this->_em->clear();

        /* @var $user User */
        $user  = $this->_em->find(User::CLASSNAME, $user->id);
        $tweet = $this->_em->find(Tweet::CLASSNAME, $tweet->id);

        $user->tweets->removeElement($tweet);

        $this->assertCount(0, $user->tweets);

        $this->_em->clear();

        /* @var $tweet Tweet */
        $tweet = $this->_em->find(Tweet::CLASSNAME, $tweet->id);
        $this->assertInstanceOf(
            Tweet::CLASSNAME,
            $tweet,
            'Even though the collection is extra lazy, the tweet should not have been deleted'
        );

        $this->assertNull($tweet->author);
    }
}
