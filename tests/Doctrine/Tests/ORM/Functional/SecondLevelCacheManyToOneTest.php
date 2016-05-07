<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\ComplexAction;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Token;
use Doctrine\Tests\Models\Cache\Action;

/**
 * @group DDC-2183
 */
class SecondLevelCacheManyToOneTest extends SecondLevelCacheAbstractTest
{
    public function testPutOnPersist()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->states[0]->getCountry()->getId()));
        self::assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->states[1]->getCountry()->getId()));
        self::assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));
    }

    public function testPutAndLoadManyToOneRelation()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        $this->cache->evictEntityRegion(State::CLASSNAME);
        $this->cache->evictEntityRegion(Country::CLASSNAME);

        self::assertFalse($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        self::assertFalse($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));
        self::assertFalse($this->cache->containsEntity(Country::CLASSNAME, $this->states[0]->getCountry()->getId()));
        self::assertFalse($this->cache->containsEntity(Country::CLASSNAME, $this->states[1]->getCountry()->getId()));

        $c1 = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());
        $c2 = $this->_em->find(State::CLASSNAME, $this->states[1]->getId());

        //trigger lazy load
        self::assertNotNull($c1->getCountry()->getName());
        self::assertNotNull($c2->getCountry()->getName());

        self::assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->states[0]->getCountry()->getId()));
        self::assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->states[1]->getCountry()->getId()));
        self::assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));

        self::assertInstanceOf(State::CLASSNAME, $c1);
        self::assertInstanceOf(State::CLASSNAME, $c2);
        self::assertInstanceOf(Country::CLASSNAME, $c1->getCountry());
        self::assertInstanceOf(Country::CLASSNAME, $c2->getCountry());

        self::assertEquals($this->states[0]->getId(), $c1->getId());
        self::assertEquals($this->states[0]->getName(), $c1->getName());
        self::assertEquals($this->states[0]->getCountry()->getId(), $c1->getCountry()->getId());
        self::assertEquals($this->states[0]->getCountry()->getName(), $c1->getCountry()->getName());

        self::assertEquals($this->states[1]->getId(), $c2->getId());
        self::assertEquals($this->states[1]->getName(), $c2->getName());
        self::assertEquals($this->states[1]->getCountry()->getId(), $c2->getCountry()->getId());
        self::assertEquals($this->states[1]->getCountry()->getName(), $c2->getCountry()->getName());

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $c3 = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());
        $c4 = $this->_em->find(State::CLASSNAME, $this->states[1]->getId());

        self::assertEquals($queryCount, $this->getCurrentQueryCount());

        //trigger lazy load from cache
        self::assertNotNull($c3->getCountry()->getName());
        self::assertNotNull($c4->getCountry()->getName());

        self::assertInstanceOf(State::CLASSNAME, $c3);
        self::assertInstanceOf(State::CLASSNAME, $c4);
        self::assertInstanceOf(Country::CLASSNAME, $c3->getCountry());
        self::assertInstanceOf(Country::CLASSNAME, $c4->getCountry());

        self::assertEquals($c1->getId(), $c3->getId());
        self::assertEquals($c1->getName(), $c3->getName());

        self::assertEquals($c2->getId(), $c4->getId());
        self::assertEquals($c2->getName(), $c4->getName());

        self::assertEquals($this->states[0]->getCountry()->getId(), $c3->getCountry()->getId());
        self::assertEquals($this->states[0]->getCountry()->getName(), $c3->getCountry()->getName());

        self::assertEquals($this->states[1]->getCountry()->getId(), $c4->getCountry()->getId());
        self::assertEquals($this->states[1]->getCountry()->getName(), $c4->getCountry()->getName());
    }

    public function testInverseSidePutShouldEvictCollection()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();

        $this->_em->clear();

        $this->cache->evictEntityRegion(State::CLASSNAME);
        $this->cache->evictEntityRegion(Country::CLASSNAME);

        //evict collection on add
        $c3    = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());
        $prev  = $c3->getCities();
        $count = $prev->count();
        $city  = new City("Buenos Aires", $c3);

        $c3->addCity($city);

        $this->_em->persist($city);
        $this->_em->persist($c3);
        $this->_em->flush();
        $this->_em->clear();

        $state      = $this->_em->find(State::CLASSNAME, $c3->getId());
        $queryCount = $this->getCurrentQueryCount();

        // Association was cleared from EM
        self::assertNotEquals($prev, $state->getCities());

        // New association has one more item (cache was evicted)
        self::assertEquals($count + 1, $state->getCities()->count());
        self::assertEquals($queryCount, $this->getCurrentQueryCount());
    }

    public function testShouldNotReloadWhenAssociationIsMissing()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        $stateId1 = $this->states[0]->getId();
        $stateId2 = $this->states[3]->getId();

        $countryId1 = $this->states[0]->getCountry()->getId();
        $countryId2 = $this->states[3]->getCountry()->getId();

        self::assertTrue($this->cache->containsEntity(Country::CLASSNAME, $countryId1));
        self::assertTrue($this->cache->containsEntity(Country::CLASSNAME, $countryId2));
        self::assertTrue($this->cache->containsEntity(State::CLASSNAME, $stateId1));
        self::assertTrue($this->cache->containsEntity(State::CLASSNAME, $stateId2));

        $this->cache->evictEntityRegion(Country::CLASSNAME);

        self::assertFalse($this->cache->containsEntity(Country::CLASSNAME, $countryId1));
        self::assertFalse($this->cache->containsEntity(Country::CLASSNAME, $countryId2));

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $state1 = $this->_em->find(State::CLASSNAME, $stateId1);
        $state2 = $this->_em->find(State::CLASSNAME, $stateId2);

        self::assertEquals($queryCount, $this->getCurrentQueryCount());

        self::assertInstanceOf(State::CLASSNAME, $state1);
        self::assertInstanceOf(State::CLASSNAME, $state2);
        self::assertInstanceOf(Country::CLASSNAME, $state1->getCountry());
        self::assertInstanceOf(Country::CLASSNAME, $state2->getCountry());

        $queryCount = $this->getCurrentQueryCount();

        self::assertNotNull($state1->getCountry()->getName());
        self::assertNotNull($state2->getCountry()->getName());
        self::assertEquals($countryId1, $state1->getCountry()->getId());
        self::assertEquals($countryId2, $state2->getCountry()->getId());

        self::assertEquals($queryCount + 2, $this->getCurrentQueryCount());
    }

    public function testPutAndLoadNonCacheableManyToOne()
    {
        self::assertNull($this->cache->getEntityCacheRegion(Action::CLASSNAME));
        self::assertInstanceOf('Doctrine\ORM\Cache\Region', $this->cache->getEntityCacheRegion(Token::CLASSNAME));

        $token  = new Token('token-hash');
        $action = new Action('exec');
        $action->addToken($token);

        $this->_em->persist($token);

        $this->_em->flush();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(Token::CLASSNAME, $token->token));
        self::assertFalse($this->cache->containsEntity(Token::CLASSNAME, $action->id));

        $queryCount = $this->getCurrentQueryCount();
        $entity = $this->_em->find(Token::CLASSNAME, $token->token);

        self::assertInstanceOf(Token::CLASSNAME, $entity);
        self::assertEquals('token-hash', $entity->token);

        self::assertInstanceOf(Action::CLASSNAME, $entity->getAction());
        self::assertEquals('exec', $entity->getAction()->name);

        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }

    public function testPutAndLoadNonCacheableCompositeManyToOne()
    {
        self::assertNull($this->cache->getEntityCacheRegion(Action::CLASSNAME));
        self::assertNull($this->cache->getEntityCacheRegion(ComplexAction::CLASSNAME));
        self::assertInstanceOf('Doctrine\ORM\Cache\Region', $this->cache->getEntityCacheRegion(Token::CLASSNAME));

        $token  = new Token('token-hash');

        $action1 = new Action('login');
        $action2 = new Action('logout');
        $action3 = new Action('rememberme');

        $complexAction = new ComplexAction($action1, $action3, 'login,rememberme');

        $complexAction->addToken($token);

        $token->action = $action2;

        $this->_em->persist($token);

        $this->_em->flush();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(Token::CLASSNAME, $token->token));
        self::assertFalse($this->cache->containsEntity(Action::CLASSNAME, $action1->id));
        self::assertFalse($this->cache->containsEntity(Action::CLASSNAME, $action2->id));
        self::assertFalse($this->cache->containsEntity(Action::CLASSNAME, $action3->id));

        $queryCount = $this->getCurrentQueryCount();
        /**
         * @var $entity Token
         */
        $entity = $this->_em->find(Token::CLASSNAME, $token->token);

        self::assertInstanceOf(Token::CLASSNAME, $entity);
        self::assertEquals('token-hash', $entity->token);

        self::assertEquals($queryCount, $this->getCurrentQueryCount());

        self::assertInstanceOf(Action::CLASSNAME, $entity->getAction());
        self::assertInstanceOf(ComplexAction::CLASSNAME, $entity->getComplexAction());
        self::assertEquals($queryCount, $this->getCurrentQueryCount());

        self::assertInstanceOf(Action::CLASSNAME, $entity->getComplexAction()->getAction1());
        self::assertInstanceOf(Action::CLASSNAME, $entity->getComplexAction()->getAction2());
        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        self::assertEquals('login', $entity->getComplexAction()->getAction1()->name);
        self::assertEquals($queryCount + 2, $this->getCurrentQueryCount());
        self::assertEquals('rememberme', $entity->getComplexAction()->getAction2()->name);
        self::assertEquals($queryCount + 3, $this->getCurrentQueryCount());
    }
}