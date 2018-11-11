<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\LazyCriteriaCollection;
use Doctrine\Tests\Models\Tweet\Tweet;
use Doctrine\Tests\Models\Tweet\User as TweetUser;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC3740Test extends OrmFunctionalTestCase
{
    /** @var LazyCriteriaCollection */
    private $lazyCriteriaCollection;

    protected function setUp() : void
    {
        $this->useModelSet('tweet');
        parent::setUp();

        $this->lazyCriteriaCollection = new LazyCriteriaCollection(
            $this->em->getUnitOfWork()->getEntityPersister(Tweet::class),
            new Criteria()
        );
    }

    public function testCountIsCached() : void
    {
        $user       = new TweetUser();
        $user->name = 'Caio';

        $tweet          = new Tweet();
        $tweet->content = 'I am a teapot!';

        $user->addTweet($tweet);

        $this->em->persist($user);
        $this->em->persist($tweet);
        $this->em->flush();
        $this->em->clear();

        self::assertSame(1, $this->lazyCriteriaCollection->count());
        self::assertSame(1, $this->lazyCriteriaCollection->count());
        self::assertSame(1, $this->lazyCriteriaCollection->count());
    }

    public function testCountIsCachedEvenWithZeroResult() : void
    {
        self::assertSame(0, $this->lazyCriteriaCollection->count());
        self::assertSame(0, $this->lazyCriteriaCollection->count());
        self::assertSame(0, $this->lazyCriteriaCollection->count());
    }
}
