<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Cache\Region;
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

        $this->assertTrue($this->cache->containsEntity(Traveler::class, $entity1->getId()));
        $this->assertTrue($this->cache->containsEntity(Traveler::class, $entity2->getId()));
        $this->assertTrue($this->cache->containsEntity(TravelerProfile::class, $entity1->getProfile()->getId()));
        $this->assertTrue($this->cache->containsEntity(TravelerProfile::class, $entity2->getProfile()->getId()));
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

        $this->assertTrue($this->cache->containsEntity(Traveler::class, $entity1->getId()));
        $this->assertTrue($this->cache->containsEntity(Traveler::class, $entity2->getId()));
        $this->assertTrue($this->cache->containsEntity(TravelerProfile::class, $entity1->getProfile()->getId()));
        $this->assertTrue($this->cache->containsEntity(TravelerProfile::class, $entity2->getProfile()->getId()));
        $this->assertTrue($this->cache->containsEntity(TravelerProfileInfo::class, $entity1->getProfile()->getInfo()->getId()));
        $this->assertTrue($this->cache->containsEntity(TravelerProfileInfo::class, $entity2->getProfile()->getInfo()->getId()));
    }

    public function testPutAndLoadOneToOneUnidirectionalRelation()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesTravelersWithProfile();
        $this->loadFixturesTravelersProfileInfo();

        $this->_em->clear();

        $this->cache->evictEntityRegion(Traveler::class);
        $this->cache->evictEntityRegion(TravelerProfile::class);

        $entity1 = $this->travelersWithProfile[0];
        $entity2 = $this->travelersWithProfile[1];

        $this->assertFalse($this->cache->containsEntity(Traveler::class, $entity1->getId()));
        $this->assertFalse($this->cache->containsEntity(Traveler::class, $entity2->getId()));
        $this->assertFalse($this->cache->containsEntity(TravelerProfile::class, $entity1->getProfile()->getId()));
        $this->assertFalse($this->cache->containsEntity(TravelerProfile::class, $entity2->getProfile()->getId()));

        $t1 = $this->_em->find(Traveler::class, $entity1->getId());
        $t2 = $this->_em->find(Traveler::class, $entity2->getId());

        $this->assertTrue($this->cache->containsEntity(Traveler::class, $entity1->getId()));
        $this->assertTrue($this->cache->containsEntity(Traveler::class, $entity2->getId()));
        // The inverse side its not cached
        $this->assertFalse($this->cache->containsEntity(TravelerProfile::class, $entity1->getProfile()->getId()));
        $this->assertFalse($this->cache->containsEntity(TravelerProfile::class, $entity2->getProfile()->getId()));

        $this->assertInstanceOf(Traveler::class, $t1);
        $this->assertInstanceOf(Traveler::class, $t2);
        $this->assertInstanceOf(TravelerProfile::class, $t1->getProfile());
        $this->assertInstanceOf(TravelerProfile::class, $t2->getProfile());

        $this->assertEquals($entity1->getId(), $t1->getId());
        $this->assertEquals($entity1->getName(), $t1->getName());
        $this->assertEquals($entity1->getProfile()->getId(), $t1->getProfile()->getId());
        $this->assertEquals($entity1->getProfile()->getName(), $t1->getProfile()->getName());

        $this->assertEquals($entity2->getId(), $t2->getId());
        $this->assertEquals($entity2->getName(), $t2->getName());
        $this->assertEquals($entity2->getProfile()->getId(), $t2->getProfile()->getId());
        $this->assertEquals($entity2->getProfile()->getName(), $t2->getProfile()->getName());

        // its all cached now
        $this->assertTrue($this->cache->containsEntity(Traveler::class, $entity1->getId()));
        $this->assertTrue($this->cache->containsEntity(Traveler::class, $entity2->getId()));
        $this->assertTrue($this->cache->containsEntity(TravelerProfile::class, $entity1->getProfile()->getId()));
        $this->assertTrue($this->cache->containsEntity(TravelerProfile::class, $entity1->getProfile()->getId()));

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        // load from cache
        $t3 = $this->_em->find(Traveler::class, $entity1->getId());
        $t4 = $this->_em->find(Traveler::class, $entity2->getId());

        $this->assertInstanceOf(Traveler::class, $t3);
        $this->assertInstanceOf(Traveler::class, $t4);
        $this->assertInstanceOf(TravelerProfile::class, $t3->getProfile());
        $this->assertInstanceOf(TravelerProfile::class, $t4->getProfile());

        $this->assertEquals($entity1->getProfile()->getId(), $t3->getProfile()->getId());
        $this->assertEquals($entity2->getProfile()->getId(), $t4->getProfile()->getId());

        $this->assertEquals($entity1->getProfile()->getName(), $t3->getProfile()->getName());
        $this->assertEquals($entity2->getProfile()->getName(), $t4->getProfile()->getName());

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
    }

    public function testPutAndLoadOneToOneBidirectionalRelation()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesTravelersWithProfile();
        $this->loadFixturesTravelersProfileInfo();

        $this->_em->clear();

        $this->cache->evictEntityRegion(Traveler::class);
        $this->cache->evictEntityRegion(TravelerProfile::class);
        $this->cache->evictEntityRegion(TravelerProfileInfo::class);

        $entity1 = $this->travelersWithProfile[0]->getProfile();
        $entity2 = $this->travelersWithProfile[1]->getProfile();

        $this->assertFalse($this->cache->containsEntity(TravelerProfile::class, $entity1->getId()));
        $this->assertFalse($this->cache->containsEntity(TravelerProfile::class, $entity2->getId()));
        $this->assertFalse($this->cache->containsEntity(TravelerProfileInfo::class, $entity1->getInfo()->getId()));
        $this->assertFalse($this->cache->containsEntity(TravelerProfileInfo::class, $entity2->getInfo()->getId()));

        $p1 = $this->_em->find(TravelerProfile::class, $entity1->getId());
        $p2 = $this->_em->find(TravelerProfile::class, $entity2->getId());

        $this->assertEquals($entity1->getId(), $p1->getId());
        $this->assertEquals($entity1->getName(), $p1->getName());
        $this->assertEquals($entity1->getInfo()->getId(), $p1->getInfo()->getId());
        $this->assertEquals($entity1->getInfo()->getDescription(), $p1->getInfo()->getDescription());

        $this->assertEquals($entity2->getId(), $p2->getId());
        $this->assertEquals($entity2->getName(), $p2->getName());
        $this->assertEquals($entity2->getInfo()->getId(), $p2->getInfo()->getId());
        $this->assertEquals($entity2->getInfo()->getDescription(), $p2->getInfo()->getDescription());

        $this->assertTrue($this->cache->containsEntity(TravelerProfile::class, $entity1->getId()));
        $this->assertTrue($this->cache->containsEntity(TravelerProfile::class, $entity2->getId()));
        $this->assertTrue($this->cache->containsEntity(TravelerProfileInfo::class, $entity1->getInfo()->getId()));
        $this->assertTrue($this->cache->containsEntity(TravelerProfileInfo::class, $entity2->getInfo()->getId()));

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $p3 = $this->_em->find(TravelerProfile::class, $entity1->getId());
        $p4 = $this->_em->find(TravelerProfile::class, $entity2->getId());

        $this->assertInstanceOf(TravelerProfile::class, $p3);
        $this->assertInstanceOf(TravelerProfile::class, $p4);
        $this->assertInstanceOf(TravelerProfileInfo::class, $p3->getInfo());
        $this->assertInstanceOf(TravelerProfileInfo::class, $p4->getInfo());

        $this->assertEquals($entity1->getId(), $p3->getId());
        $this->assertEquals($entity1->getName(), $p3->getName());
        $this->assertEquals($entity1->getInfo()->getId(), $p3->getInfo()->getId());
        $this->assertEquals($entity1->getInfo()->getDescription(), $p3->getInfo()->getDescription());

        $this->assertEquals($entity2->getId(), $p4->getId());
        $this->assertEquals($entity2->getName(), $p4->getName());
        $this->assertEquals($entity2->getInfo()->getId(), $p4->getInfo()->getId());
        $this->assertEquals($entity2->getInfo()->getDescription(), $p4->getInfo()->getDescription());

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
    }

    public function testInverseSidePutAndLoadOneToOneBidirectionalRelation()
    {
        $this->loadFixturesPersonWithAddress();

        $this->_em->clear();

        $this->cache->evictEntityRegion(Person::class);
        $this->cache->evictEntityRegion(Address::class);

        $entity1 = $this->addresses[0]->person;
        $entity2 = $this->addresses[1]->person;

        $this->assertFalse($this->cache->containsEntity(Person::class, $entity1->id));
        $this->assertFalse($this->cache->containsEntity(Person::class, $entity2->id));
        $this->assertFalse($this->cache->containsEntity(Address::class, $entity1->address->id));
        $this->assertFalse($this->cache->containsEntity(Address::class, $entity2->address->id));

        $p1 = $this->_em->find(Person::class, $entity1->id);
        $p2 = $this->_em->find(Person::class, $entity2->id);

        $this->assertEquals($entity1->id, $p1->id);
        $this->assertEquals($entity1->name, $p1->name);
        $this->assertEquals($entity1->address->id, $p1->address->id);
        $this->assertEquals($entity1->address->location, $p1->address->location);

        $this->assertEquals($entity2->id, $p2->id);
        $this->assertEquals($entity2->name, $p2->name);
        $this->assertEquals($entity2->address->id, $p2->address->id);
        $this->assertEquals($entity2->address->location, $p2->address->location);

        $this->assertTrue($this->cache->containsEntity(Person::class, $entity1->id));
        $this->assertTrue($this->cache->containsEntity(Person::class, $entity2->id));
        // The inverse side its not cached
        $this->assertFalse($this->cache->containsEntity(Address::class, $entity1->address->id));
        $this->assertFalse($this->cache->containsEntity(Address::class, $entity2->address->id));

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $p3 = $this->_em->find(Person::class, $entity1->id);
        $p4 = $this->_em->find(Person::class, $entity2->id);

        $this->assertInstanceOf(Person::class, $p3);
        $this->assertInstanceOf(Person::class, $p4);
        $this->assertInstanceOf(Address::class, $p3->address);
        $this->assertInstanceOf(Address::class, $p4->address);

        $this->assertEquals($entity1->id, $p3->id);
        $this->assertEquals($entity1->name, $p3->name);
        $this->assertEquals($entity1->address->id, $p3->address->id);
        $this->assertEquals($entity1->address->location, $p3->address->location);

        $this->assertEquals($entity2->id, $p4->id);
        $this->assertEquals($entity2->name, $p4->name);
        $this->assertEquals($entity2->address->id, $p4->address->id);
        $this->assertEquals($entity2->address->location, $p4->address->location);

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
    }

    public function testPutAndLoadNonCacheableOneToOne()
    {
        $this->assertNull($this->cache->getEntityCacheRegion(Client::class));
        $this->assertInstanceOf(Region::class, $this->cache->getEntityCacheRegion(Token::class));

        $client = new Client('FabioBatSilva');
        $token  = new Token('token-hash', $client);

        $this->_em->persist($client);
        $this->_em->persist($token);
        $this->_em->flush();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $this->assertTrue($this->cache->containsEntity(Token::class, $token->token));
        $this->assertFalse($this->cache->containsEntity(Client::class, $client->id));

        $entity = $this->_em->find(Token::class, $token->token);

        $this->assertInstanceOf(Token::class, $entity);
        $this->assertInstanceOf(Client::class, $entity->getClient());
        $this->assertEquals('token-hash', $entity->token);
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertEquals('FabioBatSilva', $entity->getClient()->name);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }
}
