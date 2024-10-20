<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Cache\Region;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\Login;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Token;
use Doctrine\Tests\Models\Cache\Travel;
use Doctrine\Tests\Models\Cache\Traveler;
use PHPUnit\Framework\Attributes\Group;

use function sprintf;

#[Group('DDC-2183')]
class SecondLevelCacheOneToManyTest extends SecondLevelCacheFunctionalTestCase
{
    public function testShouldPutCollectionInverseSideOnPersist(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));
        self::assertTrue($this->cache->containsCollection(State::class, 'cities', $this->states[0]->getId()));
        self::assertTrue($this->cache->containsCollection(State::class, 'cities', $this->states[1]->getId()));
    }

    public function testPutAndLoadOneToManyRelation(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $this->cache->evictEntityRegion(State::class);
        $this->cache->evictEntityRegion(City::class);
        $this->cache->evictCollectionRegion(State::class, 'cities');

        self::assertFalse($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertFalse($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        self::assertFalse($this->cache->containsCollection(State::class, 'cities', $this->states[0]->getId()));
        self::assertFalse($this->cache->containsCollection(State::class, 'cities', $this->states[1]->getId()));

        self::assertFalse($this->cache->containsEntity(City::class, $this->states[0]->getCities()->get(0)->getId()));
        self::assertFalse($this->cache->containsEntity(City::class, $this->states[0]->getCities()->get(1)->getId()));
        self::assertFalse($this->cache->containsEntity(City::class, $this->states[1]->getCities()->get(0)->getId()));
        self::assertFalse($this->cache->containsEntity(City::class, $this->states[1]->getCities()->get(1)->getId()));

        $s1 = $this->_em->find(State::class, $this->states[0]->getId());
        $s2 = $this->_em->find(State::class, $this->states[1]->getId());

        self::assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getMissCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::class)));
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionMissCount($this->getEntityRegion(State::class)));

        //trigger lazy load
        self::assertCount(2, $s1->getCities());
        self::assertCount(2, $s2->getCities());

        self::assertEquals(4, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(4, $this->secondLevelCacheLogger->getMissCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getCollectionRegion(State::class, 'cities')));
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionMissCount($this->getCollectionRegion(State::class, 'cities')));

        self::assertInstanceOf(City::class, $s1->getCities()->get(0));
        self::assertInstanceOf(City::class, $s1->getCities()->get(1));

        self::assertInstanceOf(City::class, $s2->getCities()->get(0));
        self::assertInstanceOf(City::class, $s2->getCities()->get(1));

        self::assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        self::assertTrue($this->cache->containsCollection(State::class, 'cities', $this->states[0]->getId()));
        self::assertTrue($this->cache->containsCollection(State::class, 'cities', $this->states[1]->getId()));

        self::assertTrue($this->cache->containsEntity(City::class, $this->states[0]->getCities()->get(0)->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->states[0]->getCities()->get(1)->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->states[1]->getCities()->get(0)->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->states[1]->getCities()->get(1)->getId()));

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $this->getQueryLog()->reset()->enable();

        $s3 = $this->_em->find(State::class, $this->states[0]->getId());
        $s4 = $this->_em->find(State::class, $this->states[1]->getId());

        //trigger lazy load from cache
        self::assertCount(2, $s3->getCities());
        self::assertCount(2, $s4->getCities());

        self::assertEquals(4, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::class)));
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getCollectionRegion(State::class, 'cities')));

        self::assertInstanceOf(City::class, $s3->getCities()->get(0));
        self::assertInstanceOf(City::class, $s3->getCities()->get(1));
        self::assertInstanceOf(City::class, $s4->getCities()->get(0));
        self::assertInstanceOf(City::class, $s4->getCities()->get(1));

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
        $this->assertQueryCount(0);
    }

    public function testLoadOneToManyCollectionFromDatabaseWhenEntityMissing(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();

        //trigger lazy load from database
        self::assertCount(2, $this->_em->find(State::class, $this->states[0]->getId())->getCities());

        self::assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsCollection(State::class, 'cities', $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->states[0]->getCities()->get(0)->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->states[0]->getCities()->get(1)->getId()));

        $this->getQueryLog()->reset()->enable();
        $stateId = $this->states[0]->getId();
        $state   = $this->_em->find(State::class, $stateId);
        $cityId  = $this->states[0]->getCities()->get(1)->getId();

        //trigger lazy load from cache
        self::assertCount(2, $state->getCities());
        $this->assertQueryCount(0);
        self::assertTrue($this->cache->containsEntity(City::class, $cityId));

        $this->cache->evictEntity(City::class, $cityId);

        self::assertFalse($this->cache->containsEntity(City::class, $cityId));
        self::assertTrue($this->cache->containsEntity(State::class, $stateId));
        self::assertTrue($this->cache->containsCollection(State::class, 'cities', $stateId));

        $this->_em->clear();

        $state = $this->_em->find(State::class, $stateId);

        //trigger lazy load from database
        self::assertCount(2, $state->getCities());
        $this->assertQueryCount(1);
    }

    public function testShoudNotPutOneToManyRelationOnPersist(): void
    {
        $this->loadFixturesCountries();
        $this->evictRegions();

        $state = new State('State Foo', $this->countries[0]);

        $this->_em->persist($state);
        $this->_em->flush();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(State::class, $state->getId()));
        self::assertFalse($this->cache->containsCollection(State::class, 'cities', $state->getId()));
    }

    public function testOneToManyRemove(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $this->cache->evictEntityRegion(State::class);
        $this->cache->evictEntityRegion(City::class);
        $this->cache->evictCollectionRegion(State::class, 'cities');

        self::assertFalse($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertFalse($this->cache->containsCollection(State::class, 'cities', $this->states[0]->getId()));
        self::assertFalse($this->cache->containsEntity(City::class, $this->states[0]->getCities()->get(0)->getId()));
        self::assertFalse($this->cache->containsEntity(City::class, $this->states[0]->getCities()->get(1)->getId()));

        $entity = $this->_em->find(State::class, $this->states[0]->getId());

        self::assertEquals(1, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getMissCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::class)));
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getEntityRegion(State::class)));

        //trigger lazy load
        self::assertCount(2, $entity->getCities());

        self::assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getMissCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getCollectionRegion(State::class, 'cities')));
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getCollectionRegion(State::class, 'cities')));

        self::assertInstanceOf(City::class, $entity->getCities()->get(0));
        self::assertInstanceOf(City::class, $entity->getCities()->get(1));

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $this->getQueryLog()->reset()->enable();
        $state = $this->_em->find(State::class, $this->states[0]->getId());

        //trigger lazy load from cache
        self::assertCount(2, $state->getCities());

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::class)));
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getCollectionRegion(State::class, 'cities')));

        $city0 = $state->getCities()->get(0);
        $city1 = $state->getCities()->get(1);

        self::assertInstanceOf(City::class, $city0);
        self::assertInstanceOf(City::class, $city1);

        self::assertEquals($entity->getCities()->get(0)->getName(), $city0->getName());
        self::assertEquals($entity->getCities()->get(1)->getName(), $city1->getName());

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        $this->assertQueryCount(0);

        $state->getCities()->removeElement($city0);

        $this->_em->remove($city0);
        $this->_em->persist($state);
        $this->_em->flush();
        $this->_em->clear();

        $this->secondLevelCacheLogger->clearStats();

        $this->getQueryLog()->reset()->enable();
        $state = $this->_em->find(State::class, $this->states[0]->getId());

        //trigger lazy load from cache
        self::assertCount(1, $state->getCities());

        $city1 = $state->getCities()->get(0);
        self::assertInstanceOf(City::class, $city1);
        self::assertEquals($entity->getCities()->get(1)->getName(), $city1->getName());

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::class)));
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getCollectionRegion(State::class, 'cities')));

        $this->assertQueryCount(0);

        $state->getCities()->remove(0);

        $this->_em->remove($city1);
        $this->_em->persist($state);
        $this->_em->flush();
        $this->_em->clear();

        $this->secondLevelCacheLogger->clearStats();

        $this->getQueryLog()->reset()->enable();
        $state = $this->_em->find(State::class, $this->states[0]->getId());

        self::assertCount(0, $state->getCities());

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::class)));
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getCollectionRegion(State::class, 'cities')));
    }

    public function testOneToManyWithEmptyRelation(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->secondLevelCacheLogger->clearStats();
        $this->cache->evictEntityRegion(City::class);
        $this->cache->evictEntityRegion(State::class);
        $this->cache->evictCollectionRegion(State::class, 'cities');
        $this->_em->clear();

        $entitiId = $this->states[2]->getId(); // bavaria (cities count = 0)
        $this->getQueryLog()->reset()->enable();
        $entity = $this->_em->find(State::class, $entitiId);

        self::assertEquals(0, $entity->getCities()->count());
        $this->assertQueryCount(2);

        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();
        $entity = $this->_em->find(State::class, $entitiId);

        self::assertEquals(0, $entity->getCities()->count());
        $this->assertQueryCount(0);
    }

    public function testOneToManyCount(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->secondLevelCacheLogger->clearStats();
        $this->cache->evictEntityRegion(City::class);
        $this->cache->evictEntityRegion(State::class);
        $this->cache->evictCollectionRegion(State::class, 'cities');
        $this->_em->clear();

        $entityId = $this->states[0]->getId();
        $this->getQueryLog()->reset()->enable();
        $entity = $this->_em->find(State::class, $entityId);

        self::assertEquals(2, $entity->getCities()->count());
        $this->assertQueryCount(2);

        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();
        $entity = $this->_em->find(State::class, $entityId);

        self::assertEquals(2, $entity->getCities()->count());
        $this->assertQueryCount(0);
    }

    public function testCacheInitializeCollectionWithNewObjects(): void
    {
        $this->_em->clear();

        $this->evictRegions();

        $traveler = new Traveler('Doctrine Bot');

        for ($i = 0; $i < 3; ++$i) {
            $traveler->getTravels()->add(new Travel($traveler));
        }

        $this->_em->persist($traveler);
        $this->_em->flush();
        $this->_em->clear();

        self::assertCount(3, $traveler->getTravels());

        $travelerId = $traveler->getId();
        $this->getQueryLog()->reset()->enable();
        $entity = $this->_em->find(Traveler::class, $travelerId);

        $this->assertQueryCount(0);
        self::assertFalse($entity->getTravels()->isInitialized());

        $newItem = new Travel($entity);
        $entity->getTravels()->add($newItem);

        self::assertFalse($entity->getTravels()->isInitialized());
        self::assertCount(4, $entity->getTravels());
        self::assertTrue($entity->getTravels()->isInitialized());
        $this->assertQueryCount(0);

        $this->_em->flush();
        $this->_em->clear();

        $query  = sprintf(
            'SELECT t, tt FROM Doctrine\Tests\Models\Cache\Traveler t JOIN t.travels tt WHERE t.id = %s',
            $travelerId,
        );
        $result = $this->_em->createQuery($query)->getSingleResult();

        self::assertEquals(4, $result->getTravels()->count());
    }

    public function testPutAndLoadNonCacheableOneToMany(): void
    {
        self::assertNull($this->cache->getEntityCacheRegion(Login::class));
        self::assertInstanceOf(Region::class, $this->cache->getEntityCacheRegion(Token::class));

        $l1    = new Login('session1');
        $l2    = new Login('session2');
        $token = new Token('token-hash');
        $token->addLogin($l1);
        $token->addLogin($l2);

        $this->_em->persist($token);
        $this->_em->flush();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(Token::class, $token->token));

        $this->getQueryLog()->reset()->enable();

        $entity = $this->_em->find(Token::class, $token->token);

        self::assertInstanceOf(Token::class, $entity);
        self::assertEquals('token-hash', $entity->token);
        $this->assertQueryCount(0);

        self::assertCount(2, $entity->logins);
        $this->assertQueryCount(1);
    }
}
