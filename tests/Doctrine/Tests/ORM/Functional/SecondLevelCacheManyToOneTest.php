<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Proxy\Proxy as CommonProxy;
use Doctrine\ORM\Cache\Region;
use Doctrine\Tests\Models\Cache\Action;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\ComplexAction;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Token;

/** @group DDC-2183 */
class SecondLevelCacheManyToOneTest extends SecondLevelCacheFunctionalTestCase
{
    public function testPutOnPersist(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(Country::class, $this->states[0]->getCountry()->getId()));
        self::assertTrue($this->cache->containsEntity(Country::class, $this->states[1]->getCountry()->getId()));
        self::assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));
    }

    public function testPutAndLoadManyToOneRelation(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        $this->cache->evictEntityRegion(State::class);
        $this->cache->evictEntityRegion(Country::class);

        self::assertFalse($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertFalse($this->cache->containsEntity(State::class, $this->states[1]->getId()));
        self::assertFalse($this->cache->containsEntity(Country::class, $this->states[0]->getCountry()->getId()));
        self::assertFalse($this->cache->containsEntity(Country::class, $this->states[1]->getCountry()->getId()));

        $c1 = $this->_em->find(State::class, $this->states[0]->getId());
        $c2 = $this->_em->find(State::class, $this->states[1]->getId());

        //trigger lazy load
        self::assertNotNull($c1->getCountry()->getName());
        self::assertNotNull($c2->getCountry()->getName());

        self::assertTrue($this->cache->containsEntity(Country::class, $this->states[0]->getCountry()->getId()));
        self::assertTrue($this->cache->containsEntity(Country::class, $this->states[1]->getCountry()->getId()));
        self::assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        self::assertInstanceOf(State::class, $c1);
        self::assertInstanceOf(State::class, $c2);
        self::assertInstanceOf(Country::class, $c1->getCountry());
        self::assertInstanceOf(Country::class, $c2->getCountry());

        self::assertEquals($this->states[0]->getId(), $c1->getId());
        self::assertEquals($this->states[0]->getName(), $c1->getName());
        self::assertEquals($this->states[0]->getCountry()->getId(), $c1->getCountry()->getId());
        self::assertEquals($this->states[0]->getCountry()->getName(), $c1->getCountry()->getName());

        self::assertEquals($this->states[1]->getId(), $c2->getId());
        self::assertEquals($this->states[1]->getName(), $c2->getName());
        self::assertEquals($this->states[1]->getCountry()->getId(), $c2->getCountry()->getId());
        self::assertEquals($this->states[1]->getCountry()->getName(), $c2->getCountry()->getName());

        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();

        $c3 = $this->_em->find(State::class, $this->states[0]->getId());
        $c4 = $this->_em->find(State::class, $this->states[1]->getId());

        $this->assertQueryCount(0);

        //trigger lazy load from cache
        self::assertNotNull($c3->getCountry()->getName());
        self::assertNotNull($c4->getCountry()->getName());

        self::assertInstanceOf(State::class, $c3);
        self::assertInstanceOf(State::class, $c4);
        self::assertInstanceOf(Country::class, $c3->getCountry());
        self::assertInstanceOf(Country::class, $c4->getCountry());

        self::assertEquals($c1->getId(), $c3->getId());
        self::assertEquals($c1->getName(), $c3->getName());

        self::assertEquals($c2->getId(), $c4->getId());
        self::assertEquals($c2->getName(), $c4->getName());

        self::assertEquals($this->states[0]->getCountry()->getId(), $c3->getCountry()->getId());
        self::assertEquals($this->states[0]->getCountry()->getName(), $c3->getCountry()->getName());

        self::assertEquals($this->states[1]->getCountry()->getId(), $c4->getCountry()->getId());
        self::assertEquals($this->states[1]->getCountry()->getName(), $c4->getCountry()->getName());
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

        $state = $this->_em->find(State::class, $c3->getId());
        $this->getQueryLog()->reset()->enable();

        // Association was cleared from EM
        self::assertNotEquals($prev, $state->getCities());

        // New association has one more item (cache was evicted)
        self::assertEquals($count + 1, $state->getCities()->count());
        $this->assertQueryCount(0);
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

        self::assertTrue($this->cache->containsEntity(Country::class, $countryId1));
        self::assertTrue($this->cache->containsEntity(Country::class, $countryId2));
        self::assertTrue($this->cache->containsEntity(State::class, $stateId1));
        self::assertTrue($this->cache->containsEntity(State::class, $stateId2));

        $this->cache->evictEntityRegion(Country::class);

        self::assertFalse($this->cache->containsEntity(Country::class, $countryId1));
        self::assertFalse($this->cache->containsEntity(Country::class, $countryId2));

        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();

        $state1 = $this->_em->find(State::class, $stateId1);
        $state2 = $this->_em->find(State::class, $stateId2);

        $this->assertQueryCount(0);

        self::assertInstanceOf(State::class, $state1);
        self::assertInstanceOf(State::class, $state2);
        self::assertInstanceOf(Country::class, $state1->getCountry());
        self::assertInstanceOf(Country::class, $state2->getCountry());

        $this->getQueryLog()->reset()->enable();

        self::assertNotNull($state1->getCountry()->getName());
        self::assertNotNull($state2->getCountry()->getName());
        self::assertEquals($countryId1, $state1->getCountry()->getId());
        self::assertEquals($countryId2, $state2->getCountry()->getId());

        $this->assertQueryCount(2);
    }

    public function testPutAndLoadNonCacheableManyToOne(): void
    {
        self::assertNull($this->cache->getEntityCacheRegion(Action::class));
        self::assertInstanceOf(Region::class, $this->cache->getEntityCacheRegion(Token::class));

        $token  = new Token('token-hash');
        $action = new Action('exec');
        $action->addToken($token);

        $this->_em->persist($token);

        $this->_em->flush();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(Token::class, $token->token));
        self::assertFalse($this->cache->containsEntity(Token::class, $action->name));

        $this->getQueryLog()->reset()->enable();
        $entity = $this->_em->find(Token::class, $token->token);

        self::assertInstanceOf(Token::class, $entity);
        self::assertEquals('token-hash', $entity->token);

        self::assertInstanceOf(Action::class, $entity->getAction());
        self::assertEquals('exec', $entity->getAction()->name);

        $this->assertQueryCount(0);
    }

    public function testPutAndLoadNonCacheableCompositeManyToOne(): void
    {
        self::assertNull($this->cache->getEntityCacheRegion(Action::class));
        self::assertNull($this->cache->getEntityCacheRegion(ComplexAction::class));
        self::assertInstanceOf(Region::class, $this->cache->getEntityCacheRegion(Token::class));

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

        self::assertTrue($this->cache->containsEntity(Token::class, $token->token));
        self::assertFalse($this->cache->containsEntity(Action::class, $action1->name));
        self::assertFalse($this->cache->containsEntity(Action::class, $action2->name));
        self::assertFalse($this->cache->containsEntity(Action::class, $action3->name));

        $this->getQueryLog()->reset()->enable();

        $entity = $this->_em->find(Token::class, $token->token);

        self::assertInstanceOf(Token::class, $entity);
        self::assertEquals('token-hash', $entity->token);

        $this->assertQueryCount(0);

        self::assertInstanceOf(Action::class, $entity->getAction());
        self::assertInstanceOf(ComplexAction::class, $entity->getComplexAction());
        $this->assertQueryCount(0);

        self::assertInstanceOf(Action::class, $entity->getComplexAction()->getAction1());
        self::assertInstanceOf(Action::class, $entity->getComplexAction()->getAction2());
        $expectedQueryCount = $entity->getAction() instanceof CommonProxy ? 1 : 0;
        $this->assertQueryCount($expectedQueryCount);

        self::assertEquals('login', $entity->getComplexAction()->getAction1()->name);
        $this->assertQueryCount($expectedQueryCount);
        self::assertEquals('rememberme', $entity->getComplexAction()->getAction2()->name);
        $this->assertQueryCount($expectedQueryCount);
    }
}
