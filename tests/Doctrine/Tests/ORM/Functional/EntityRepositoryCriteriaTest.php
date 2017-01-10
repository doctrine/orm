<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\LazyCriteriaCollection;
use Doctrine\Tests\Models\Generic\DateTimeModel;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Tests\Models\Tweet\Tweet;
use Doctrine\Tests\Models\Tweet\User;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @author Josiah <josiah@jjs.id.au>
 */
class EntityRepositoryCriteriaTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('generic');
        $this->useModelSet('tweet');
        parent::setUp();
    }

    public function tearDown()
    {
        if ($this->em) {
            $this->em->getConfiguration()->setEntityNamespaces([]);
        }
        parent::tearDown();
    }

    public function loadFixture()
    {
        $today = new DateTimeModel();
        $today->datetime =
        $today->date =
        $today->time =
            new \DateTime('today');
        $this->em->persist($today);

        $tomorrow = new DateTimeModel();
        $tomorrow->datetime =
        $tomorrow->date =
        $tomorrow->time =
            new \DateTime('tomorrow');
        $this->em->persist($tomorrow);

        $yesterday = new DateTimeModel();
        $yesterday->datetime =
        $yesterday->date =
        $yesterday->time =
            new \DateTime('yesterday');
        $this->em->persist($yesterday);

        $this->em->flush();

        unset($today);
        unset($tomorrow);
        unset($yesterday);

        $this->em->clear();
    }

    public function testLteDateComparison()
    {
        $this->loadFixture();

        $repository = $this->em->getRepository(DateTimeModel::class);
        $dates = $repository->matching(new Criteria(
            Criteria::expr()->lte('datetime', new \DateTime('today'))
        ));

        self::assertEquals(2, count($dates));
    }

    private function loadNullFieldFixtures()
    {
        $today = new DateTimeModel();
        $today->datetime =
        $today->date =
            new \DateTime('today');

        $this->em->persist($today);

        $tomorrow = new DateTimeModel();
        $tomorrow->datetime =
        $tomorrow->date =
        $tomorrow->time =
            new \DateTime('tomorrow');
        $this->em->persist($tomorrow);

        $this->em->flush();
        $this->em->clear();
    }

    public function testIsNullComparison()
    {
        $this->loadNullFieldFixtures();
        $repository = $this->em->getRepository(DateTimeModel::class);

        $dates = $repository->matching(new Criteria(
            Criteria::expr()->isNull('time')
        ));

        self::assertEquals(1, count($dates));
    }

    public function testEqNullComparison()
    {
        $this->loadNullFieldFixtures();
        $repository = $this->em->getRepository(DateTimeModel::class);

        $dates = $repository->matching(new Criteria(
            Criteria::expr()->eq('time', null)
        ));

        self::assertEquals(1, count($dates));
    }

    public function testNotEqNullComparison()
    {
        $this->loadNullFieldFixtures();
        $repository = $this->em->getRepository(DateTimeModel::class);

        $dates = $repository->matching(new Criteria(
            Criteria::expr()->neq('time', null)
        ));

        self::assertEquals(1, count($dates));
    }

    public function testCanCountWithoutLoadingCollection()
    {
        $this->loadFixture();
        $repository = $this->em->getRepository(DateTimeModel::class);

        $dates = $repository->matching(new Criteria());

        self::assertFalse($dates->isInitialized());
        self::assertCount(3, $dates);
        self::assertFalse($dates->isInitialized());

        // Test it can work even with a constraint
        $dates = $repository->matching(new Criteria(
            Criteria::expr()->lte('datetime', new \DateTime('today'))
        ));

        self::assertFalse($dates->isInitialized());
        self::assertCount(2, $dates);
        self::assertFalse($dates->isInitialized());

        // Trigger a loading, to make sure collection is initialized
        $date = $dates[0];
        self::assertTrue($dates->isInitialized());
    }

    public function testCanContainsWithoutLoadingCollection()
    {
        $user = new User();
        $user->name = 'Marco';
        $this->em->persist($user);
        $this->em->flush();

        $tweet = new Tweet();
        $tweet->author = $user;
        $tweet->content = 'Criteria is awesome';
        $this->em->persist($tweet);
        $this->em->flush();

        $this->em->clear();

        $criteria = new Criteria();
        $criteria->andWhere($criteria->expr()->contains('content', 'Criteria'));

        $user   = $this->em->find(User::class, $user->id);
        $tweets = $user->tweets->matching($criteria);

        self::assertInstanceOf(LazyCriteriaCollection::class, $tweets);
        self::assertFalse($tweets->isInitialized());

        $tweets->contains($tweet);
        self::assertTrue($tweets->contains($tweet));

        self::assertFalse($tweets->isInitialized());
    }
}
