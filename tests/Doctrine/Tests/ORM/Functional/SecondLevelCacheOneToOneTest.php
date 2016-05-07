<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\Address;
use Doctrine\Tests\Models\Cache\Client;
use Doctrine\Tests\Models\Cache\Person;
use Doctrine\Tests\Models\Cache\Token;
use Doctrine\Tests\Models\Cache\Traveler;
use Doctrine\Tests\Models\Cache\TravelerProfile;
use Doctrine\Tests\Models\Cache\TravelerProfileInfo;

/**
 * @group DDC-2183
 */
class SecondLevelCacheOneToOneTest extends SecondLevelCacheAbstractTest
{
    public function testPutOneToOneOnUnidirectionalPersist()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesTravelersWithProfile();

        $this->_em->clear();

        $entity1 = $this->travelersWithProfile[0];
        $entity2 = $this->travelersWithProfile[1];

        self::assertTrue($this->cache->containsEntity(Traveler::CLASSNAME, $entity1->getId()));
        self::assertTrue($this->cache->containsEntity(Traveler::CLASSNAME, $entity2->getId()));
        self::assertTrue($this->cache->containsEntity(TravelerProfile::CLASSNAME, $entity1->getProfile()->getId()));
        self::assertTrue($this->cache->containsEntity(TravelerProfile::CLASSNAME, $entity2->getProfile()->getId()));
    }

    public function testPutOneToOneOnBidirectionalPersist()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesTravelersWithProfile();
        $this->loadFixturesTravelersProfileInfo();

        $this->_em->clear();

        $entity1 = $this->travelersWithProfile[0];
        $entity2 = $this->travelersWithProfile[1];

        self::assertTrue($this->cache->containsEntity(Traveler::CLASSNAME, $entity1->getId()));
        self::assertTrue($this->cache->containsEntity(Traveler::CLASSNAME, $entity2->getId()));
        self::assertTrue($this->cache->containsEntity(TravelerProfile::CLASSNAME, $entity1->getProfile()->getId()));
        self::assertTrue($this->cache->containsEntity(TravelerProfile::CLASSNAME, $entity2->getProfile()->getId()));
        self::assertTrue($this->cache->containsEntity(TravelerProfileInfo::CLASSNAME, $entity1->getProfile()->getInfo()->getId()));
        self::assertTrue($this->cache->containsEntity(TravelerProfileInfo::CLASSNAME, $entity2->getProfile()->getInfo()->getId()));
    }

    public function testPutAndLoadOneToOneUnidirectionalRelation()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesTravelersWithProfile();
        $this->loadFixturesTravelersProfileInfo();

        $this->_em->clear();

        $this->cache->evictEntityRegion(Traveler::CLASSNAME);
        $this->cache->evictEntityRegion(TravelerProfile::CLASSNAME);

        $entity1 = $this->travelersWithProfile[0];
        $entity2 = $this->travelersWithProfile[1];

        self::assertFalse($this->cache->containsEntity(Traveler::CLASSNAME, $entity1->getId()));
        self::assertFalse($this->cache->containsEntity(Traveler::CLASSNAME, $entity2->getId()));
        self::assertFalse($this->cache->containsEntity(TravelerProfile::CLASSNAME, $entity1->getProfile()->getId()));
        self::assertFalse($this->cache->containsEntity(TravelerProfile::CLASSNAME, $entity2->getProfile()->getId()));

        $t1 = $this->_em->find(Traveler::CLASSNAME, $entity1->getId());
        $t2 = $this->_em->find(Traveler::CLASSNAME, $entity2->getId());

        self::assertTrue($this->cache->containsEntity(Traveler::CLASSNAME, $entity1->getId()));
        self::assertTrue($this->cache->containsEntity(Traveler::CLASSNAME, $entity2->getId()));
        // The inverse side its not cached
        self::assertFalse($this->cache->containsEntity(TravelerProfile::CLASSNAME, $entity1->getProfile()->getId()));
        self::assertFalse($this->cache->containsEntity(TravelerProfile::CLASSNAME, $entity2->getProfile()->getId()));

        self::assertInstanceOf(Traveler::CLASSNAME, $t1);
        self::assertInstanceOf(Traveler::CLASSNAME, $t2);
        self::assertInstanceOf(TravelerProfile::CLASSNAME, $t1->getProfile());
        self::assertInstanceOf(TravelerProfile::CLASSNAME, $t2->getProfile());

        self::assertEquals($entity1->getId(), $t1->getId());
        self::assertEquals($entity1->getName(), $t1->getName());
        self::assertEquals($entity1->getProfile()->getId(), $t1->getProfile()->getId());
        self::assertEquals($entity1->getProfile()->getName(), $t1->getProfile()->getName());

        self::assertEquals($entity2->getId(), $t2->getId());
        self::assertEquals($entity2->getName(), $t2->getName());
        self::assertEquals($entity2->getProfile()->getId(), $t2->getProfile()->getId());
        self::assertEquals($entity2->getProfile()->getName(), $t2->getProfile()->getName());

        // its all cached now
        self::assertTrue($this->cache->containsEntity(Traveler::CLASSNAME, $entity1->getId()));
        self::assertTrue($this->cache->containsEntity(Traveler::CLASSNAME, $entity2->getId()));
        self::assertTrue($this->cache->containsEntity(TravelerProfile::CLASSNAME, $entity1->getProfile()->getId()));
        self::assertTrue($this->cache->containsEntity(TravelerProfile::CLASSNAME, $entity1->getProfile()->getId()));

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        // load from cache
        $t3 = $this->_em->find(Traveler::CLASSNAME, $entity1->getId());
        $t4 = $this->_em->find(Traveler::CLASSNAME, $entity2->getId());

        self::assertInstanceOf(Traveler::CLASSNAME, $t3);
        self::assertInstanceOf(Traveler::CLASSNAME, $t4);
        self::assertInstanceOf(TravelerProfile::CLASSNAME, $t3->getProfile());
        self::assertInstanceOf(TravelerProfile::CLASSNAME, $t4->getProfile());

        self::assertEquals($entity1->getProfile()->getId(), $t3->getProfile()->getId());
        self::assertEquals($entity2->getProfile()->getId(), $t4->getProfile()->getId());

        self::assertEquals($entity1->getProfile()->getName(), $t3->getProfile()->getName());
        self::assertEquals($entity2->getProfile()->getName(), $t4->getProfile()->getName());

        self::assertEquals($queryCount, $this->getCurrentQueryCount());
    }

    public function testPutAndLoadOneToOneBidirectionalRelation()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesTravelersWithProfile();
        $this->loadFixturesTravelersProfileInfo();

        $this->_em->clear();

        $this->cache->evictEntityRegion(Traveler::CLASSNAME);
        $this->cache->evictEntityRegion(TravelerProfile::CLASSNAME);
        $this->cache->evictEntityRegion(TravelerProfileInfo::CLASSNAME);

        $entity1 = $this->travelersWithProfile[0]->getProfile();
        $entity2 = $this->travelersWithProfile[1]->getProfile();

        self::assertFalse($this->cache->containsEntity(TravelerProfile::CLASSNAME, $entity1->getId()));
        self::assertFalse($this->cache->containsEntity(TravelerProfile::CLASSNAME, $entity2->getId()));
        self::assertFalse($this->cache->containsEntity(TravelerProfileInfo::CLASSNAME, $entity1->getInfo()->getId()));
        self::assertFalse($this->cache->containsEntity(TravelerProfileInfo::CLASSNAME, $entity2->getInfo()->getId()));

        $p1 = $this->_em->find(TravelerProfile::CLASSNAME, $entity1->getId());
        $p2 = $this->_em->find(TravelerProfile::CLASSNAME, $entity2->getId());

        self::assertEquals($entity1->getId(), $p1->getId());
        self::assertEquals($entity1->getName(), $p1->getName());
        self::assertEquals($entity1->getInfo()->getId(), $p1->getInfo()->getId());
        self::assertEquals($entity1->getInfo()->getDescription(), $p1->getInfo()->getDescription());

        self::assertEquals($entity2->getId(), $p2->getId());
        self::assertEquals($entity2->getName(), $p2->getName());
        self::assertEquals($entity2->getInfo()->getId(), $p2->getInfo()->getId());
        self::assertEquals($entity2->getInfo()->getDescription(), $p2->getInfo()->getDescription());

        self::assertTrue($this->cache->containsEntity(TravelerProfile::CLASSNAME, $entity1->getId()));
        self::assertTrue($this->cache->containsEntity(TravelerProfile::CLASSNAME, $entity2->getId()));
        self::assertTrue($this->cache->containsEntity(TravelerProfileInfo::CLASSNAME, $entity1->getInfo()->getId()));
        self::assertTrue($this->cache->containsEntity(TravelerProfileInfo::CLASSNAME, $entity2->getInfo()->getId()));

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $p3 = $this->_em->find(TravelerProfile::CLASSNAME, $entity1->getId());
        $p4 = $this->_em->find(TravelerProfile::CLASSNAME, $entity2->getId());

        self::assertInstanceOf(TravelerProfile::CLASSNAME, $p3);
        self::assertInstanceOf(TravelerProfile::CLASSNAME, $p4);
        self::assertInstanceOf(TravelerProfileInfo::CLASSNAME, $p3->getInfo());
        self::assertInstanceOf(TravelerProfileInfo::CLASSNAME, $p4->getInfo());

        self::assertEquals($entity1->getId(), $p3->getId());
        self::assertEquals($entity1->getName(), $p3->getName());
        self::assertEquals($entity1->getInfo()->getId(), $p3->getInfo()->getId());
        self::assertEquals($entity1->getInfo()->getDescription(), $p3->getInfo()->getDescription());

        self::assertEquals($entity2->getId(), $p4->getId());
        self::assertEquals($entity2->getName(), $p4->getName());
        self::assertEquals($entity2->getInfo()->getId(), $p4->getInfo()->getId());
        self::assertEquals($entity2->getInfo()->getDescription(), $p4->getInfo()->getDescription());

        self::assertEquals($queryCount, $this->getCurrentQueryCount());
    }

    public function testInverseSidePutAndLoadOneToOneBidirectionalRelation()
    {
        $this->loadFixturesPersonWithAddress();

        $this->_em->clear();

        $this->cache->evictEntityRegion(Person::CLASSNAME);
        $this->cache->evictEntityRegion(Address::CLASSNAME);

        $entity1 = $this->addresses[0]->person;
        $entity2 = $this->addresses[1]->person;

        self::assertFalse($this->cache->containsEntity(Person::CLASSNAME, $entity1->id));
        self::assertFalse($this->cache->containsEntity(Person::CLASSNAME, $entity2->id));
        self::assertFalse($this->cache->containsEntity(Address::CLASSNAME, $entity1->address->id));
        self::assertFalse($this->cache->containsEntity(Address::CLASSNAME, $entity2->address->id));

        $p1 = $this->_em->find(Person::CLASSNAME, $entity1->id);
        $p2 = $this->_em->find(Person::CLASSNAME, $entity2->id);

        self::assertEquals($entity1->id, $p1->id);
        self::assertEquals($entity1->name, $p1->name);
        self::assertEquals($entity1->address->id, $p1->address->id);
        self::assertEquals($entity1->address->location, $p1->address->location);

        self::assertEquals($entity2->id, $p2->id);
        self::assertEquals($entity2->name, $p2->name);
        self::assertEquals($entity2->address->id, $p2->address->id);
        self::assertEquals($entity2->address->location, $p2->address->location);

        self::assertTrue($this->cache->containsEntity(Person::CLASSNAME, $entity1->id));
        self::assertTrue($this->cache->containsEntity(Person::CLASSNAME, $entity2->id));
        // The inverse side its not cached
        self::assertFalse($this->cache->containsEntity(Address::CLASSNAME, $entity1->address->id));
        self::assertFalse($this->cache->containsEntity(Address::CLASSNAME, $entity2->address->id));

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $p3 = $this->_em->find(Person::CLASSNAME, $entity1->id);
        $p4 = $this->_em->find(Person::CLASSNAME, $entity2->id);

        self::assertInstanceOf(Person::CLASSNAME, $p3);
        self::assertInstanceOf(Person::CLASSNAME, $p4);
        self::assertInstanceOf(Address::CLASSNAME, $p3->address);
        self::assertInstanceOf(Address::CLASSNAME, $p4->address);

        self::assertEquals($entity1->id, $p3->id);
        self::assertEquals($entity1->name, $p3->name);
        self::assertEquals($entity1->address->id, $p3->address->id);
        self::assertEquals($entity1->address->location, $p3->address->location);

        self::assertEquals($entity2->id, $p4->id);
        self::assertEquals($entity2->name, $p4->name);
        self::assertEquals($entity2->address->id, $p4->address->id);
        self::assertEquals($entity2->address->location, $p4->address->location);

        self::assertEquals($queryCount + 2, $this->getCurrentQueryCount());
    }

    public function testPutAndLoadNonCacheableOneToOne()
    {
        self::assertNull($this->cache->getEntityCacheRegion(Client::CLASSNAME));
        self::assertInstanceOf('Doctrine\ORM\Cache\Region', $this->cache->getEntityCacheRegion(Token::CLASSNAME));

        $client = new Client('FabioBatSilva');
        $token  = new Token('token-hash', $client);

        $this->_em->persist($client);
        $this->_em->persist($token);
        $this->_em->flush();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        self::assertTrue($this->cache->containsEntity(Token::CLASSNAME, $token->token));
        self::assertFalse($this->cache->containsEntity(Client::CLASSNAME, $client->id));

        $entity = $this->_em->find(Token::CLASSNAME, $token->token);

        self::assertInstanceOf(Token::CLASSNAME, $entity);
        self::assertInstanceOf(Client::CLASSNAME, $entity->getClient());
        self::assertEquals('token-hash', $entity->token);
        self::assertEquals($queryCount, $this->getCurrentQueryCount());

        self::assertEquals('FabioBatSilva', $entity->getClient()->name);
        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }
}