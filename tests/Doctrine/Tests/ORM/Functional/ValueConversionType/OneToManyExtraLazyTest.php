<?php

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\Tests\Models\Tweet\Tweet;
use Doctrine\Tests\Models\Tweet\User;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * The entities all use a custom type that converst the value as identifier(s).
 * {@see \Doctrine\Tests\DbalTypes\Rot13Type}
 *
 * Test that OneToMany associations work correctly, focusing on EXTRA_LAZY
 * functionality.
 *
 * @group DDC-3380
 */
class OneToManyExtraLazyTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('tweet');

        parent::setUp();
    }

    /**
     * @group DDC-3343
     */
    public function testRemovesManagedElementFromOneToManyExtraLazyCollection()
    {
        list($userId, $tweetId) = $this->loadTweetFixture();

        /* @var $user User */
        $user = $this->_em->find(User::CLASSNAME, $userId);

        $user->tweets->removeElement($this->_em->find(Tweet::CLASSNAME, $tweetId));

        $this->_em->clear();

        /* @var $user User */
        $user = $this->_em->find(User::CLASSNAME, $userId);

        $this->assertCount(0, $user->tweets);
    }

    /**
     * @group DDC-3343
     */
    public function testRemovesManagedElementFromOneToManyExtraLazyCollectionWithoutDeletingTheTargetEntityEntry()
    {
        list($userId, $tweetId) = $this->loadTweetFixture();

        /* @var $user User */
        $user  = $this->_em->find(User::CLASSNAME, $userId);

        $user->tweets->removeElement($this->_em->find(Tweet::CLASSNAME, $tweetId));

        $this->_em->clear();

        /* @var $tweet Tweet */
        $tweet = $this->_em->find(Tweet::CLASSNAME, $tweetId);
        $this->assertInstanceOf(
            Tweet::CLASSNAME,
            $tweet,
            'Even though the collection is extra lazy, the tweet should not have been deleted'
        );

        $this->assertNull($tweet->author, 'Tweet author link has been removed');
    }

    /**
     * @group DDC-3343
     */
    public function testRemovingManagedLazyProxyFromExtraLazyOneToManyDoesRemoveTheAssociationButNotTheEntity()
    {
        list($userId, $tweetId) = $this->loadTweetFixture();

        /* @var $user User */
        $user  = $this->_em->find(User::CLASSNAME, $userId);
        $tweet = $this->_em->getReference(Tweet::CLASSNAME, $tweetId);

        $user->tweets->removeElement($this->_em->getReference(Tweet::CLASSNAME, $tweetId));

        $this->_em->clear();

        /* @var $tweet Tweet */
        $tweet = $this->_em->find(Tweet::CLASSNAME, $tweet->id);
        $this->assertInstanceOf(
            Tweet::CLASSNAME,
            $tweet,
            'Even though the collection is extra lazy, the tweet should not have been deleted'
        );

        $this->assertNull($tweet->author);

        /* @var $user User */
        $user = $this->_em->find(User::CLASSNAME, $userId);

        $this->assertCount(0, $user->tweets);
    }

    /**
     * @return int[] ordered tuple: user id and tweet id
     */
    private function loadTweetFixture()
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

        return array($user->id, $tweet->id);
    }
}
