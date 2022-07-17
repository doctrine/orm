<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;
use Exception;
use RuntimeException;

use function uniqid;

/**
 * @group DDC-2183
 */
class SecondLevelCacheTest extends SecondLevelCacheFunctionalTestCase
{
    public function testPutOnPersist(): void
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));
        self::assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));
    }

    public function testPutAndLoadEntities(): void
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        self::assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));

        $this->cache->evictEntityRegion(Country::class);

        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $c1 = $this->_em->find(Country::class, $this->countries[0]->getId());
        $c2 = $this->_em->find(Country::class, $this->countries[1]->getId());

        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        self::assertInstanceOf(Country::class, $c1);
        self::assertInstanceOf(Country::class, $c2);

        self::assertEquals($this->countries[0]->getId(), $c1->getId());
        self::assertEquals($this->countries[0]->getName(), $c1->getName());

        self::assertEquals($this->countries[1]->getId(), $c2->getId());
        self::assertEquals($this->countries[1]->getName(), $c2->getName());

        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();

        $c3 = $this->_em->find(Country::class, $this->countries[0]->getId());
        $c4 = $this->_em->find(Country::class, $this->countries[1]->getId());

        $this->assertQueryCount(0);
        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(Country::class)));

        self::assertInstanceOf(Country::class, $c3);
        self::assertInstanceOf(Country::class, $c4);

        self::assertEquals($c1->getId(), $c3->getId());
        self::assertEquals($c1->getName(), $c3->getName());

        self::assertEquals($c2->getId(), $c4->getId());
        self::assertEquals($c2->getName(), $c4->getName());
    }

    public function testRemoveEntities(): void
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        self::assertEquals(2, $this->secondLevelCacheLogger->getPutCount());

        $this->cache->evictEntityRegion(Country::class);
        $this->secondLevelCacheLogger->clearRegionStats($this->getEntityRegion(Country::class));

        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $c1 = $this->_em->find(Country::class, $this->countries[0]->getId());
        $c2 = $this->_em->find(Country::class, $this->countries[1]->getId());

        self::assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getMissCount());

        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        self::assertInstanceOf(Country::class, $c1);
        self::assertInstanceOf(Country::class, $c2);

        self::assertEquals($this->countries[0]->getId(), $c1->getId());
        self::assertEquals($this->countries[0]->getName(), $c1->getName());

        self::assertEquals($this->countries[1]->getId(), $c2->getId());
        self::assertEquals($this->countries[1]->getName(), $c2->getName());

        $this->_em->remove($c1);
        $this->_em->remove($c2);
        $this->_em->flush();
        $this->_em->clear();

        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        self::assertNull($this->_em->find(Country::class, $this->countries[0]->getId()));
        self::assertNull($this->_em->find(Country::class, $this->countries[1]->getId()));

        self::assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getMissCount());
    }

    public function testUpdateEntities(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        self::assertEquals(6, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));
        self::assertEquals(4, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::class)));

        $this->cache->evictEntityRegion(State::class);
        $this->secondLevelCacheLogger->clearRegionStats($this->getEntityRegion(State::class));

        self::assertFalse($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertFalse($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        $s1 = $this->_em->find(State::class, $this->states[0]->getId());
        $s2 = $this->_em->find(State::class, $this->states[1]->getId());

        self::assertEquals(4, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::class)));

        self::assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        self::assertInstanceOf(State::class, $s1);
        self::assertInstanceOf(State::class, $s2);

        self::assertEquals($this->states[0]->getId(), $s1->getId());
        self::assertEquals($this->states[0]->getName(), $s1->getName());

        self::assertEquals($this->states[1]->getId(), $s2->getId());
        self::assertEquals($this->states[1]->getName(), $s2->getName());

        $s1->setName('NEW NAME 1');
        $s2->setName('NEW NAME 2');

        $this->_em->persist($s1);
        $this->_em->persist($s2);
        $this->_em->flush();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        self::assertEquals(6, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));
        self::assertEquals(4, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::class)));

        $this->getQueryLog()->reset()->enable();

        $c3 = $this->_em->find(State::class, $this->states[0]->getId());
        $c4 = $this->_em->find(State::class, $this->states[1]->getId());

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::class)));

        $this->assertQueryCount(0);

        self::assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        self::assertInstanceOf(State::class, $c3);
        self::assertInstanceOf(State::class, $c4);

        self::assertEquals($s1->getId(), $c3->getId());
        self::assertEquals('NEW NAME 1', $c3->getName());

        self::assertEquals($s2->getId(), $c4->getId());
        self::assertEquals('NEW NAME 2', $c4->getName());

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::class)));
    }

    public function testPostFlushFailure(): void
    {
        $listener = new ListenerSecondLevelCacheTest(
            [
                Events::postFlush => static function (): void {
                    throw new RuntimeException('post flush failure');
                },
            ]
        );

        $this->_em->getEventManager()
            ->addEventListener(Events::postFlush, $listener);

        $country = new Country('Brazil');

        $this->cache->evictEntityRegion(Country::class);

        try {
            $this->_em->persist($country);
            $this->_em->flush();
            self::fail('Should throw exception');
        } catch (RuntimeException $exc) {
            self::assertNotNull($country->getId());
            self::assertEquals('post flush failure', $exc->getMessage());
            self::assertTrue($this->cache->containsEntity(Country::class, $country->getId()));
        }
    }

    public function testPostUpdateFailure(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        $listener = new ListenerSecondLevelCacheTest(
            [
                Events::postUpdate => static function (): void {
                    throw new RuntimeException('post update failure');
                },
            ]
        );

        $this->_em->getEventManager()
            ->addEventListener(Events::postUpdate, $listener);

        $this->cache->evictEntityRegion(State::class);

        $stateId   = $this->states[0]->getId();
        $stateName = $this->states[0]->getName();
        $state     = $this->_em->find(State::class, $stateId);

        self::assertTrue($this->cache->containsEntity(State::class, $stateId));
        self::assertInstanceOf(State::class, $state);
        self::assertEquals($stateName, $state->getName());

        $state->setName($stateName . uniqid());

        $this->_em->persist($state);

        try {
            $this->_em->flush();
            self::fail('Should throw exception');
        } catch (Exception $exc) {
            self::assertEquals('post update failure', $exc->getMessage());
        }

        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(State::class, $stateId));

        $state = $this->_em->find(State::class, $stateId);

        self::assertInstanceOf(State::class, $state);
        self::assertEquals($stateName, $state->getName());
    }

    public function testPostRemoveFailure(): void
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        $listener = new ListenerSecondLevelCacheTest(
            [
                Events::postRemove => static function (): void {
                    throw new RuntimeException('post remove failure');
                },
            ]
        );

        $this->_em->getEventManager()
            ->addEventListener(Events::postRemove, $listener);

        $this->cache->evictEntityRegion(Country::class);

        $countryId = $this->countries[0]->getId();
        $country   = $this->_em->find(Country::class, $countryId);

        self::assertTrue($this->cache->containsEntity(Country::class, $countryId));
        self::assertInstanceOf(Country::class, $country);

        $this->_em->remove($country);

        try {
            $this->_em->flush();
            self::fail('Should throw exception');
        } catch (Exception $exc) {
            self::assertEquals('post remove failure', $exc->getMessage());
        }

        $this->_em->clear();

        self::assertFalse(
            $this->cache->containsEntity(Country::class, $countryId),
            'Removal attempts should clear the cache entry corresponding to the entity'
        );

        self::assertInstanceOf(Country::class, $this->_em->find(Country::class, $countryId));
    }

    public function testCachedNewEntityExists(): void
    {
        $this->loadFixturesCountries();

        $persister = $this->_em->getUnitOfWork()->getEntityPersister(Country::class);
        $this->getQueryLog()->reset()->enable();

        self::assertTrue($persister->exists($this->countries[0]));

        $this->assertQueryCount(0);

        self::assertFalse($persister->exists(new Country('Foo')));
    }
}


class ListenerSecondLevelCacheTest
{
    /** @var array<string, callable> */
    public $callbacks;

    /**
     * @psalm-param array<string, callable> $callbacks
     */
    public function __construct(array $callbacks = [])
    {
        $this->callbacks = $callbacks;
    }

    private function dispatch(string $eventName, EventArgs $args): void
    {
        if (isset($this->callbacks[$eventName])) {
            ($this->callbacks[$eventName])($args);
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $this->dispatch(__FUNCTION__, $args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->dispatch(__FUNCTION__, $args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->dispatch(__FUNCTION__, $args);
    }
}
