<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\OrmFunctionalTestCase;
use function array_values;

/**
 * @group gh7864
 */
class GH7864Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setup();

        $this->setUpEntitySchema(
            [
                GH7864User::class,
                GH7864Tweet::class,
            ]
        );
    }

    public function testExtraLazyRemoveElement()
    {
        $user       = new GH7864User();
        $user->name = 'test';

        $tweet1          = new GH7864Tweet();
        $tweet1->content = 'Hello World!';
        $user->addTweet($tweet1);

        $tweet2          = new GH7864Tweet();
        $tweet2->content = 'Goodbye, and thanks for all the fish';
        $user->addTweet($tweet2);

        $this->_em->persist($user);
        $this->_em->persist($tweet1);
        $this->_em->persist($tweet2);
        $this->_em->flush();
        $this->_em->clear();

        $user  = $this->_em->find(GH7864User::class, $user->id);
        $tweet = $this->_em->find(GH7864Tweet::class, $tweet1->id);

        $user->tweets->removeElement($tweet);

        $tweets = $user->tweets->map(static function (GH7864Tweet $tweet) {
            return $tweet->content;
        });

        $this->assertEquals(['Goodbye, and thanks for all the fish'], array_values($tweets->toArray()));
    }
}

/**
 * @Entity
 */
class GH7864User
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @Column(type="string") */
    public $name;

    /** @OneToMany(targetEntity="GH7864Tweet", mappedBy="user", fetch="EXTRA_LAZY") */
    public $tweets;

    public function __construct()
    {
        $this->tweets = new ArrayCollection();
    }

    public function addTweet(GH7864Tweet $tweet)
    {
        $tweet->user = $this;
        $this->tweets->add($tweet);
    }
}

/**
 * @Entity
 */
class GH7864Tweet
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @Column(type="string") */
    public $content;

    /** @ManyToOne(targetEntity="GH7864User", inversedBy="tweets") */
    public $user;
}
