<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\Login;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Token;
use Doctrine\Tests\Models\Cache\Travel;
use Doctrine\Tests\Models\Cache\Traveler;

/**
 * @group DDC-2183
 */
class SecondLevelCacheOneToManyTest extends SecondLevelCacheAbstractTest
{
    public function testShouldPutCollectionInverseSideOnPersist()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));
        self::assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[0]->getId()));
        self::assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[1]->getId()));
    }

    public function testPutAndLoadOneToManyRelation()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $this->cache->evictEntityRegion(State::CLASSNAME);
        $this->cache->evictEntityRegion(City::CLASSNAME);
        $this->cache->evictCollectionRegion(State::CLASSNAME, 'cities');

        self::assertFalse($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        self::assertFalse($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));

        self::assertFalse($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[0]->getId()));
        self::assertFalse($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[1]->getId()));

        self::assertFalse($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(0)->getId()));
        self::assertFalse($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(1)->getId()));
        self::assertFalse($this->cache->containsEntity(City::CLASSNAME, $this->states[1]->getCities()->get(0)->getId()));
        self::assertFalse($this->cache->containsEntity(City::CLASSNAME, $this->states[1]->getCities()->get(1)->getId()));

        $s1 = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());
        $s2 = $this->_em->find(State::CLASSNAME, $this->states[1]->getId());

        self::assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getMissCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::CLASSNAME)));
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionMissCount($this->getEntityRegion(State::CLASSNAME)));

        //trigger lazy load
        self::assertCount(2, $s1->getCities());
        self::assertCount(2, $s2->getCities());

        self::assertEquals(4, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(4, $this->secondLevelCacheLogger->getMissCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getCollectionRegion(State::CLASSNAME, 'cities')));
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionMissCount($this->getCollectionRegion(State::CLASSNAME, 'cities')));

        self::assertInstanceOf(City::CLASSNAME, $s1->getCities()->get(0));
        self::assertInstanceOf(City::CLASSNAME, $s1->getCities()->get(1));

        self::assertInstanceOf(City::CLASSNAME, $s2->getCities()->get(0));
        self::assertInstanceOf(City::CLASSNAME, $s2->getCities()->get(1));

        self::assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));

        self::assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[0]->getId()));
        self::assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[1]->getId()));

        self::assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(0)->getId()));
        self::assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(1)->getId()));
        self::assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[1]->getCities()->get(0)->getId()));
        self::assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[1]->getCities()->get(1)->getId()));

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $queryCount = $this->getCurrentQueryCount();

        $s3 = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());
        $s4 = $this->_em->find(State::CLASSNAME, $this->states[1]->getId());

        //trigger lazy load from cache
        self::assertCount(2, $s3->getCities());
        self::assertCount(2, $s4->getCities());

        self::assertEquals(4, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::CLASSNAME)));
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getCollectionRegion(State::CLASSNAME, 'cities')));

        self::assertInstanceOf(City::CLASSNAME, $s3->getCities()->get(0));
        self::assertInstanceOf(City::CLASSNAME, $s3->getCities()->get(1));
        self::assertInstanceOf(City::CLASSNAME, $s4->getCities()->get(0));
        self::assertInstanceOf(City::CLASSNAME, $s4->getCities()->get(1));

        self::assertNotSame($s1->getCities()->get(0), $s3->getCities()->get(0));
        self::assertEquals($s1->getCities()->get(0)->getId(), $s3->getCities()->get(0)->getId());
        self::assertEquals($s1->getCities()->get(0)->getName(), $s3->getCities()->get(0)->getName());

        self::assertNotSame($s1->getCities()->get(1), $s3->getCities()->get(1));
        self::assertEquals($s1->getCities()->get(1)->getId(), $s3->getCities()->get(1)->getId());
        self::assertEquals($s1->getCities()->get(1)->getName(), $s3->getCities()->get(1)->getName());

        self::assertNotSame($s2->getCities()->get(0), $s4->getCities()->get(0));
        self::assertEquals($s2->getCities()->get(0)->getId(), $s4->getCities()->get(0)->getId());
        self::assertEquals($s2->getCities()->get(0)->getName(), $s4->getCities()->get(0)->getName());

        self::assertNotSame($s2->getCities()->get(1), $s4->getCities()->get(1));
        self::assertEquals($s2->getCities()->get(1)->getId(), $s4->getCities()->get(1)->getId());
        self::assertEquals($s2->getCities()->get(1)->getName(), $s4->getCities()->get(1)->getName());

        self::assertEquals(4, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals($queryCount, $this->getCurrentQueryCount());
    }

    public function testLoadOneToManyCollectionFromDatabaseWhenEntityMissing()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();

        //trigger lazy load from database
        self::assertCount(2, $this->_em->find(State::CLASSNAME, $this->states[0]->getId())->getCities());

        self::assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(0)->getId()));
        self::assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(1)->getId()));

        $queryCount = $this->getCurrentQueryCount();
        $stateId    = $this->states[0]->getId();
        $state      = $this->_em->find(State::CLASSNAME, $stateId);
        $cityId     = $this->states[0]->getCities()->get(1)->getId();

        //trigger lazy load from cache
        self::assertCount(2, $state->getCities());
        self::assertEquals($queryCount, $this->getCurrentQueryCount());
        self::assertTrue($this->cache->containsEntity(City::CLASSNAME, $cityId));

        $this->cache->evictEntity(City::CLASSNAME, $cityId);

        self::assertFalse($this->cache->containsEntity(City::CLASSNAME, $cityId));
        self::assertTrue($this->cache->containsEntity(State::CLASSNAME, $stateId));
        self::assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $stateId));

        $this->_em->clear();

        $state = $this->_em->find(State::CLASSNAME, $stateId);

        //trigger lazy load from database
        self::assertCount(2, $state->getCities());
        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }


    public function testShoudNotPutOneToManyRelationOnPersist()
    {
        $this->loadFixturesCountries();
        $this->evictRegions();

        $state = new State("State Foo", $this->countries[0]);

        $this->_em->persist($state);
        $this->_em->flush();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(State::CLASSNAME, $state->getId()));
        self::assertFalse($this->cache->containsCollection(State::CLASSNAME, 'cities', $state->getId()));
    }

    public function testOneToManyRemove()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $this->cache->evictEntityRegion(State::CLASSNAME);
        $this->cache->evictEntityRegion(City::CLASSNAME);
        $this->cache->evictCollectionRegion(State::CLASSNAME, 'cities');

        self::assertFalse($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        self::assertFalse($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[0]->getId()));
        self::assertFalse($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(0)->getId()));
        self::assertFalse($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(1)->getId()));

        $entity = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());

        self::assertEquals(1, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getMissCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::CLASSNAME)));
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getEntityRegion(State::CLASSNAME)));

        //trigger lazy load
        self::assertCount(2, $entity->getCities());

        self::assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getMissCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getCollectionRegion(State::CLASSNAME, 'cities')));
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getCollectionRegion(State::CLASSNAME, 'cities')));

        self::assertInstanceOf(City::CLASSNAME, $entity->getCities()->get(0));
        self::assertInstanceOf(City::CLASSNAME, $entity->getCities()->get(1));

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $queryCount = $this->getCurrentQueryCount();
        $state      = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());

        //trigger lazy load from cache
        self::assertCount(2, $state->getCities());

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::CLASSNAME)));
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getCollectionRegion(State::CLASSNAME, 'cities')));

        $city0 = $state->getCities()->get(0);
        $city1 = $state->getCities()->get(1);

        self::assertInstanceOf(City::CLASSNAME, $city0);
        self::assertInstanceOf(City::CLASSNAME, $city1);

        self::assertEquals($entity->getCities()->get(0)->getName(), $city0->getName());
        self::assertEquals($entity->getCities()->get(1)->getName(), $city1->getName());

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals($queryCount, $this->getCurrentQueryCount());

        $state->getCities()->removeElement($city0);

        $this->_em->remove($city0);
        $this->_em->persist($state);
        $this->_em->flush();
        $this->_em->clear();

        $this->secondLevelCacheLogger->clearStats();

        $queryCount = $this->getCurrentQueryCount();
        $state      = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());

        //trigger lazy load from cache
        self::assertCount(1, $state->getCities());

        $city1 = $state->getCities()->get(0);
        self::assertInstanceOf(City::CLASSNAME, $city1);
        self::assertEquals($entity->getCities()->get(1)->getName(), $city1->getName());

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::CLASSNAME)));
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getCollectionRegion(State::CLASSNAME, 'cities')));

        self::assertEquals($queryCount, $this->getCurrentQueryCount());

        $state->getCities()->remove(0);

        $this->_em->remove($city1);
        $this->_em->persist($state);
        $this->_em->flush();
        $this->_em->clear();

        $this->secondLevelCacheLogger->clearStats();

        $queryCount = $this->getCurrentQueryCount();
        $state      = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());

        self::assertCount(0, $state->getCities());

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::CLASSNAME)));
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getCollectionRegion(State::CLASSNAME, 'cities')));
    }

    public function testOneToManyWithEmptyRelation()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->secondLevelCacheLogger->clearStats();
        $this->cache->evictEntityRegion(City::CLASSNAME);
        $this->cache->evictEntityRegion(State::CLASSNAME);
        $this->cache->evictCollectionRegion(State::CLASSNAME, 'cities');
        $this->_em->clear();

        $entitiId   = $this->states[2]->getId(); // bavaria (cities count = 0)
        $queryCount = $this->getCurrentQueryCount();
        $entity     = $this->_em->find(State::CLASSNAME, $entitiId);

        self::assertEquals(0, $entity->getCities()->count());
        self::assertEquals($queryCount + 2, $this->getCurrentQueryCount());

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $entity     = $this->_em->find(State::CLASSNAME, $entitiId);

        self::assertEquals(0, $entity->getCities()->count());
        self::assertEquals($queryCount, $this->getCurrentQueryCount());

    }

    public function testOneToManyCount()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->secondLevelCacheLogger->clearStats();
        $this->cache->evictEntityRegion(City::CLASSNAME);
        $this->cache->evictEntityRegion(State::CLASSNAME);
        $this->cache->evictCollectionRegion(State::CLASSNAME, 'cities');
        $this->_em->clear();

        $entityId   = $this->states[0]->getId();
        $queryCount = $this->getCurrentQueryCount();
        $entity     = $this->_em->find(State::CLASSNAME, $entityId);

        self::assertEquals(2, $entity->getCities()->count());
        self::assertEquals($queryCount + 2, $this->getCurrentQueryCount());

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $entity     = $this->_em->find(State::CLASSNAME, $entityId);

        self::assertEquals(2, $entity->getCities()->count());
        self::assertEquals($queryCount, $this->getCurrentQueryCount());
    }

    public function testCacheInitializeCollectionWithNewObjects()
    {
        $this->_em->clear();

        $this->evictRegions();

        $traveler = new Traveler("Doctrine Bot");

        for ($i = 0; $i < 3; ++$i) {
            $traveler->getTravels()->add(new Travel($traveler));
        }

        $this->_em->persist($traveler);
        $this->_em->flush();
        $this->_em->clear();

        self::assertCount(3, $traveler->getTravels());

        $travelerId = $traveler->getId();
        $queryCount = $this->getCurrentQueryCount();
        $entity     = $this->_em->find(Traveler::CLASSNAME, $travelerId);

        self::assertEquals($queryCount, $this->getCurrentQueryCount());
        self::assertFalse($entity->getTravels()->isInitialized());

        $newItem = new Travel($entity);
        $entity->getTravels()->add($newItem);

        self::assertFalse($entity->getTravels()->isInitialized());
        self::assertCount(4, $entity->getTravels());
        self::assertTrue($entity->getTravels()->isInitialized());
        self::assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->_em->flush();
        $this->_em->clear();

        $query  = "SELECT t, tt FROM Doctrine\Tests\Models\Cache\Traveler t JOIN t.travels tt WHERE t.id = $travelerId";
        $result = $this->_em->createQuery($query)->getSingleResult();

        self::assertEquals(4, $result->getTravels()->count());
    }

    public function testPutAndLoadNonCacheableOneToMany()
    {
        self::assertNull($this->cache->getEntityCacheRegion(Login::CLASSNAME));
        self::assertInstanceOf('Doctrine\ORM\Cache\Region', $this->cache->getEntityCacheRegion(Token::CLASSNAME));

        $l1 = new Login('session1');
        $l2 = new Login('session2');
        $token  = new Token('token-hash');
        $token->addLogin($l1);
        $token->addLogin($l2);

        $this->_em->persist($token);
        $this->_em->flush();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(Token::CLASSNAME, $token->token));

        $queryCount = $this->getCurrentQueryCount();

        $entity = $this->_em->find(Token::CLASSNAME, $token->token);

        self::assertInstanceOf(Token::CLASSNAME, $entity);
        self::assertEquals('token-hash', $entity->token);
        self::assertEquals($queryCount, $this->getCurrentQueryCount());

        self::assertCount(2, $entity->logins);
        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }
}
