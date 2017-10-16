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
        if ($this->_em) {
            $this->_em->getConfiguration()->setEntityNamespaces([]);
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
        $this->_em->persist($today);

        $tomorrow = new DateTimeModel();
        $tomorrow->datetime =
        $tomorrow->date =
        $tomorrow->time =
            new \DateTime('tomorrow');
        $this->_em->persist($tomorrow);

        $yesterday = new DateTimeModel();
        $yesterday->datetime =
        $yesterday->date =
        $yesterday->time =
            new \DateTime('yesterday');
        $this->_em->persist($yesterday);

        $this->_em->flush();

        unset($today);
        unset($tomorrow);
        unset($yesterday);

        $this->_em->clear();
    }

    public function testLteDateComparison()
    {
        $this->loadFixture();

        $repository = $this->_em->getRepository(DateTimeModel::class);
        $dates = $repository->matching(new Criteria(
            Criteria::expr()->lte('datetime', new \DateTime('today'))
        ));

        $this->assertEquals(2, count($dates));
    }

    private function loadNullFieldFixtures()
    {
        $today = new DateTimeModel();
        $today->datetime =
        $today->date =
            new \DateTime('today');

        $this->_em->persist($today);

        $tomorrow = new DateTimeModel();
        $tomorrow->datetime =
        $tomorrow->date =
        $tomorrow->time =
            new \DateTime('tomorrow');
        $this->_em->persist($tomorrow);

        $this->_em->flush();
        $this->_em->clear();
    }

    public function testIsNullComparison()
    {
        $this->loadNullFieldFixtures();
        $repository = $this->_em->getRepository(DateTimeModel::class);

        $dates = $repository->matching(new Criteria(
            Criteria::expr()->isNull('time')
        ));

        $this->assertEquals(1, count($dates));
    }

    public function testEqNullComparison()
    {
        $this->loadNullFieldFixtures();
        $repository = $this->_em->getRepository(DateTimeModel::class);

        $dates = $repository->matching(new Criteria(
            Criteria::expr()->eq('time', null)
        ));

        $this->assertEquals(1, count($dates));
    }

    public function testNotEqNullComparison()
    {
        $this->loadNullFieldFixtures();
        $repository = $this->_em->getRepository(DateTimeModel::class);

        $dates = $repository->matching(new Criteria(
            Criteria::expr()->neq('time', null)
        ));

        $this->assertEquals(1, count($dates));
    }

    public function testCanCountWithoutLoadingCollection()
    {
        $this->loadFixture();
        $repository = $this->_em->getRepository(DateTimeModel::class);

        $dates = $repository->matching(new Criteria());

        $this->assertFalse($dates->isInitialized());
        $this->assertCount(3, $dates);
        $this->assertFalse($dates->isInitialized());

        // Test it can work even with a constraint
        $dates = $repository->matching(new Criteria(
            Criteria::expr()->lte('datetime', new \DateTime('today'))
        ));

        $this->assertFalse($dates->isInitialized());
        $this->assertCount(2, $dates);
        $this->assertFalse($dates->isInitialized());

        // Trigger a loading, to make sure collection is initialized
        $date = $dates[0];
        $this->assertTrue($dates->isInitialized());
    }

    public function testCanContainsWithoutLoadingCollection()
    {
        $user = new User();
        $user->name = 'Marco';
        $this->_em->persist($user);
        $this->_em->flush();

        $tweet = new Tweet();
        $tweet->author = $user;
        $tweet->content = 'Criteria is awesome';
        $this->_em->persist($tweet);
        $this->_em->flush();

        $this->_em->clear();

        $criteria = new Criteria();
        $criteria->andWhere($criteria->expr()->contains('content', 'Criteria'));

        $user   = $this->_em->find(User::class, $user->id);
        $tweets = $user->tweets->matching($criteria);

        $this->assertInstanceOf(LazyCriteriaCollection::class, $tweets);
        $this->assertFalse($tweets->isInitialized());

        $tweets->contains($tweet);
        $this->assertTrue($tweets->contains($tweet));

        $this->assertFalse($tweets->isInitialized());
    }
}
