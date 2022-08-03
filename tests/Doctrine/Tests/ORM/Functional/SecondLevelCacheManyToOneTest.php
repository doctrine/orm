<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Cache\Region;
use Doctrine\Tests\Models\Cache\Action;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\ComplexAction;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Token;

/**
 * @group DDC-2183
 */
class SecondLevelCacheManyToOneTest extends SecondLevelCacheAbstractTest
{
    public function testPutOnPersist(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->states[0]->getCountry()->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->states[1]->getCountry()->getId()));
        $this->assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));
    }

    public function testPutAndLoadManyToOneRelation(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        $this->cache->evictEntityRegion(State::class);
        $this->cache->evictEntityRegion(Country::class);

        $this->assertFalse($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(State::class, $this->states[1]->getId()));
        $this->assertFalse($this->cache->containsEntity(Country::class, $this->states[0]->getCountry()->getId()));
        $this->assertFalse($this->cache->containsEntity(Country::class, $this->states[1]->getCountry()->getId()));

        $c1 = $this->_em->find(State::class, $this->states[0]->getId());
        $c2 = $this->_em->find(State::class, $this->states[1]->getId());

        //trigger lazy load
        $this->assertNotNull($c1->getCountry()->getName());
        $this->assertNotNull($c2->getCountry()->getName());

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->states[0]->getCountry()->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->states[1]->getCountry()->getId()));
        $this->assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        $this->assertInstanceOf(State::class, $c1);
        $this->assertInstanceOf(State::class, $c2);
        $this->assertInstanceOf(Country::class, $c1->getCountry());
        $this->assertInstanceOf(Country::class, $c2->getCountry());

        $this->assertEquals($this->states[0]->getId(), $c1->getId());
        $this->assertEquals($this->states[0]->getName(), $c1->getName());
        $this->assertEquals($this->states[0]->getCountry()->getId(), $c1->getCountry()->getId());
        $this->assertEquals($this->states[0]->getCountry()->getName(), $c1->getCountry()->getName());

        $this->assertEquals($this->states[1]->getId(), $c2->getId());
        $this->assertEquals($this->states[1]->getName(), $c2->getName());
        $this->assertEquals($this->states[1]->getCountry()->getId(), $c2->getCountry()->getId());
        $this->assertEquals($this->states[1]->getCountry()->getName(), $c2->getCountry()->getName());

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $c3 = $this->_em->find(State::class, $this->states[0]->getId());
        $c4 = $this->_em->find(State::class, $this->states[1]->getId());

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        //trigger lazy load from cache
        $this->assertNotNull($c3->getCountry()->getName());
        $this->assertNotNull($c4->getCountry()->getName());

        $this->assertInstanceOf(State::class, $c3);
        $this->assertInstanceOf(State::class, $c4);
        $this->assertInstanceOf(Country::class, $c3->getCountry());
        $this->assertInstanceOf(Country::class, $c4->getCountry());

        $this->assertEquals($c1->getId(), $c3->getId());
        $this->assertEquals($c1->getName(), $c3->getName());

        $this->assertEquals($c2->getId(), $c4->getId());
        $this->assertEquals($c2->getName(), $c4->getName());

        $this->assertEquals($this->states[0]->getCountry()->getId(), $c3->getCountry()->getId());
        $this->assertEquals($this->states[0]->getCountry()->getName(), $c3->getCountry()->getName());

        $this->assertEquals($this->states[1]->getCountry()->getId(), $c4->getCountry()->getId());
        $this->assertEquals($this->states[1]->getCountry()->getName(), $c4->getCountry()->getName());
    }

    public function testInverseSidePutShouldEvictCollection(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();

        $this->_em->clear();

        $this->cache->evictEntityRegion(State::class);
        $this->cache->evictEntityRegion(Country::class);

        //evict collection on add
        $c3    = $this->_em->find(State::class, $this->states[0]->getId());
        $prev  = $c3->getCities();
        $count = $prev->count();
        $city  = new City('Buenos Aires', $c3);

        $c3->addCity($city);

        $this->_em->persist($city);
        $this->_em->persist($c3);
        $this->_em->flush();
        $this->_em->clear();

        $state      = $this->_em->find(State::class, $c3->getId());
        $queryCount = $this->getCurrentQueryCount();

        // Association was cleared from EM
        $this->assertNotEquals($prev, $state->getCities());

        // New association has one more item (cache was evicted)
        $this->assertEquals($count + 1, $state->getCities()->count());
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
    }

    public function testShouldNotReloadWhenAssociationIsMissing(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        $stateId1 = $this->states[0]->getId();
        $stateId2 = $this->states[3]->getId();

        $countryId1 = $this->states[0]->getCountry()->getId();
        $countryId2 = $this->states[3]->getCountry()->getId();

        $this->assertTrue($this->cache->containsEntity(Country::class, $countryId1));
        $this->assertTrue($this->cache->containsEntity(Country::class, $countryId2));
        $this->assertTrue($this->cache->containsEntity(State::class, $stateId1));
        $this->assertTrue($this->cache->containsEntity(State::class, $stateId2));

        $this->cache->evictEntityRegion(Country::class);

        $this->assertFalse($this->cache->containsEntity(Country::class, $countryId1));
        $this->assertFalse($this->cache->containsEntity(Country::class, $countryId2));

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $state1 = $this->_em->find(State::class, $stateId1);
        $state2 = $this->_em->find(State::class, $stateId2);

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertInstanceOf(State::class, $state1);
        $this->assertInstanceOf(State::class, $state2);
        $this->assertInstanceOf(Country::class, $state1->getCountry());
        $this->assertInstanceOf(Country::class, $state2->getCountry());

        $queryCount = $this->getCurrentQueryCount();

        $this->assertNotNull($state1->getCountry()->getName());
        $this->assertNotNull($state2->getCountry()->getName());
        $this->assertEquals($countryId1, $state1->getCountry()->getId());
        $this->assertEquals($countryId2, $state2->getCountry()->getId());

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
    }

    public function testPutAndLoadNonCacheableManyToOne(): void
    {
        $this->assertNull($this->cache->getEntityCacheRegion(Action::class));
        $this->assertInstanceOf(Region::class, $this->cache->getEntityCacheRegion(Token::class));

        $token  = new Token('token-hash');
        $action = new Action('exec');
        $action->addToken($token);

        $this->_em->persist($token);

        $this->_em->flush();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Token::class, $token->token));
        $this->assertFalse($this->cache->containsEntity(Token::class, $action->name));

        $queryCount = $this->getCurrentQueryCount();
        $entity     = $this->_em->find(Token::class, $token->token);

        $this->assertInstanceOf(Token::class, $entity);
        $this->assertEquals('token-hash', $entity->token);

        $this->assertInstanceOf(Action::class, $entity->getAction());
        $this->assertEquals('exec', $entity->getAction()->name);

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
    }

    public function testPutAndLoadNonCacheableCompositeManyToOne(): void
    {
        $this->assertNull($this->cache->getEntityCacheRegion(Action::class));
        $this->assertNull($this->cache->getEntityCacheRegion(ComplexAction::class));
        $this->assertInstanceOf(Region::class, $this->cache->getEntityCacheRegion(Token::class));

        $token = new Token('token-hash');

        $action1 = new Action('login');
        $action2 = new Action('logout');
        $action3 = new Action('rememberme');

        $complexAction = new ComplexAction($action1, $action3, 'login,rememberme');

        $complexAction->addToken($token);

        $token->action = $action2;

        $this->_em->persist($token);

        $this->_em->flush();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Token::class, $token->token));
        $this->assertFalse($this->cache->containsEntity(Action::class, $action1->name));
        $this->assertFalse($this->cache->containsEntity(Action::class, $action2->name));
        $this->assertFalse($this->cache->containsEntity(Action::class, $action3->name));

        $queryCount = $this->getCurrentQueryCount();
        /**
         * @var $entity Token
         */
        $entity = $this->_em->find(Token::class, $token->token);

        $this->assertInstanceOf(Token::class, $entity);
        $this->assertEquals('token-hash', $entity->token);

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertInstanceOf(Action::class, $entity->getAction());
        $this->assertInstanceOf(ComplexAction::class, $entity->getComplexAction());
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertInstanceOf(Action::class, $entity->getComplexAction()->getAction1());
        $this->assertInstanceOf(Action::class, $entity->getComplexAction()->getAction2());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->assertEquals('login', $entity->getComplexAction()->getAction1()->name);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertEquals('rememberme', $entity->getComplexAction()->getAction2()->name);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }
}
