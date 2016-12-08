<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\Models\Cache\Attraction;
use Doctrine\Tests\Models\Cache\AttractionContactInfo;
use Doctrine\Tests\Models\Cache\AttractionInfo;
use Doctrine\Tests\Models\Cache\AttractionLocationInfo;

/**
 * @group DDC-2183
 */
class SecondLevelCacheJoinTableInheritanceTest extends SecondLevelCacheAbstractTest
{
    public function testUseSameRegion()
    {
        $infoRegion     = $this->cache->getEntityCacheRegion(AttractionInfo::class);
        $contactRegion  = $this->cache->getEntityCacheRegion(AttractionContactInfo::class);
        $locationRegion = $this->cache->getEntityCacheRegion(AttractionLocationInfo::class);

        $this->assertEquals($infoRegion->getName(), $contactRegion->getName());
        $this->assertEquals($infoRegion->getName(), $locationRegion->getName());
    }

    public function testPutOnPersistJoinTableInheritance()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();
        $this->loadFixturesAttractionsInfo();

        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(AttractionInfo::class, $this->attractionsInfo[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(AttractionInfo::class, $this->attractionsInfo[1]->getId()));
        $this->assertTrue($this->cache->containsEntity(AttractionInfo::class, $this->attractionsInfo[2]->getId()));
        $this->assertTrue($this->cache->containsEntity(AttractionInfo::class, $this->attractionsInfo[3]->getId()));
    }

    public function testJoinTableCountaisRootClass()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();
        $this->loadFixturesAttractionsInfo();

        $this->_em->clear();

        foreach ($this->attractionsInfo as $info) {
            $this->assertTrue($this->cache->containsEntity(AttractionInfo::class, $info->getId()));
            $this->assertTrue($this->cache->containsEntity(get_class($info), $info->getId()));
        }
    }

    public function testPutAndLoadJoinTableEntities()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();
        $this->loadFixturesAttractionsInfo();

        $this->_em->clear();

        $this->cache->evictEntityRegion(AttractionInfo::class);

        $entityId1 = $this->attractionsInfo[0]->getId();
        $entityId2 = $this->attractionsInfo[1]->getId();

        $this->assertFalse($this->cache->containsEntity(AttractionInfo::class, $entityId1));
        $this->assertFalse($this->cache->containsEntity(AttractionInfo::class, $entityId2));
        $this->assertFalse($this->cache->containsEntity(AttractionContactInfo::class, $entityId1));
        $this->assertFalse($this->cache->containsEntity(AttractionContactInfo::class, $entityId2));

        $queryCount = $this->getCurrentQueryCount();
        $entity1    = $this->_em->find(AttractionInfo::class, $entityId1);
        $entity2    = $this->_em->find(AttractionInfo::class, $entityId2);

        //load entity and relation whit sub classes
        $this->assertEquals($queryCount + 4, $this->getCurrentQueryCount());

        $this->assertTrue($this->cache->containsEntity(AttractionInfo::class, $entityId1));
        $this->assertTrue($this->cache->containsEntity(AttractionInfo::class, $entityId2));
        $this->assertTrue($this->cache->containsEntity(AttractionContactInfo::class, $entityId1));
        $this->assertTrue($this->cache->containsEntity(AttractionContactInfo::class, $entityId2));

        $this->assertInstanceOf(AttractionInfo::class, $entity1);
        $this->assertInstanceOf(AttractionInfo::class, $entity2);
        $this->assertInstanceOf(AttractionContactInfo::class, $entity1);
        $this->assertInstanceOf(AttractionContactInfo::class, $entity2);

        $this->assertEquals($this->attractionsInfo[0]->getId(), $entity1->getId());
        $this->assertEquals($this->attractionsInfo[0]->getFone(), $entity1->getFone());

        $this->assertEquals($this->attractionsInfo[1]->getId(), $entity2->getId());
        $this->assertEquals($this->attractionsInfo[1]->getFone(), $entity2->getFone());

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $entity3    = $this->_em->find(AttractionInfo::class, $entityId1);
        $entity4    = $this->_em->find(AttractionInfo::class, $entityId2);

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertInstanceOf(AttractionInfo::class, $entity3);
        $this->assertInstanceOf(AttractionInfo::class, $entity4);
        $this->assertInstanceOf(AttractionContactInfo::class, $entity3);
        $this->assertInstanceOf(AttractionContactInfo::class, $entity4);

        $this->assertNotSame($entity1, $entity3);
        $this->assertEquals($entity1->getId(), $entity3->getId());
        $this->assertEquals($entity1->getFone(), $entity3->getFone());

        $this->assertNotSame($entity2, $entity4);
        $this->assertEquals($entity2->getId(), $entity4->getId());
        $this->assertEquals($entity2->getFone(), $entity4->getFone());
    }

    public function testQueryCacheFindAllJoinTableEntities()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();
        $this->loadFixturesAttractionsInfo();
        $this->evictRegions();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT i, a FROM Doctrine\Tests\Models\Cache\AttractionInfo i JOIN i.attraction a';
        $result1    = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertCount(count($this->attractionsInfo), $result1);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->_em->clear();

        $result2 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertCount(count($this->attractionsInfo), $result2);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        foreach ($result2 as $entity) {
            $this->assertInstanceOf(AttractionInfo::class, $entity);
        }
    }

    public function testOneToManyRelationJoinTable()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();
        $this->loadFixturesAttractionsInfo();
        $this->evictRegions();
        $this->_em->clear();

        $entity = $this->_em->find(Attraction::class, $this->attractions[0]->getId());

        $this->assertInstanceOf(Attraction::class, $entity);
        $this->assertInstanceOf(PersistentCollection::class, $entity->getInfos());
        $this->assertCount(1, $entity->getInfos());

        $ownerId    = $this->attractions[0]->getId();
        $queryCount = $this->getCurrentQueryCount();

        $this->assertTrue($this->cache->containsEntity(Attraction::class, $ownerId));
        $this->assertTrue($this->cache->containsCollection(Attraction::class, 'infos', $ownerId));

        $this->assertInstanceOf(AttractionContactInfo::class, $entity->getInfos()->get(0));
        $this->assertEquals($this->attractionsInfo[0]->getFone(), $entity->getInfos()->get(0)->getFone());

        $this->_em->clear();

        $entity = $this->_em->find(Attraction::class, $this->attractions[0]->getId());

        $this->assertInstanceOf(Attraction::class, $entity);
        $this->assertInstanceOf(PersistentCollection::class, $entity->getInfos());
        $this->assertCount(1, $entity->getInfos());

        $this->assertInstanceOf(AttractionContactInfo::class, $entity->getInfos()->get(0));
        $this->assertEquals($this->attractionsInfo[0]->getFone(), $entity->getInfos()->get(0)->getFone());
    }

    public function testQueryCacheShouldBeEvictedOnTimestampUpdate()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();
        $this->loadFixturesAttractionsInfo();
        $this->evictRegions();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT attractionInfo FROM Doctrine\Tests\Models\Cache\AttractionInfo attractionInfo';

        $result1    = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertCount(count($this->attractionsInfo), $result1);
        $this->assertEquals($queryCount + 5, $this->getCurrentQueryCount());

        $contact = new AttractionContactInfo(
            '1234-1234',
            $this->_em->find(Attraction::class, $this->attractions[5]->getId())
        );

        $this->_em->persist($contact);
        $this->_em->flush();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $result2 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertCount(count($this->attractionsInfo) + 1, $result2);
        $this->assertEquals($queryCount + 6, $this->getCurrentQueryCount());

        foreach ($result2 as $entity) {
            $this->assertInstanceOf(AttractionInfo::class, $entity);
        }
    }
}
