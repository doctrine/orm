<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\Attraction;
use Doctrine\Tests\Models\Cache\AttractionInfo;
use Doctrine\Tests\Models\Cache\AttractionContactInfo;
use Doctrine\Tests\Models\Cache\AttractionLocationInfo;

/**
 * @group DDC-2183
 */
class SecondLevelCacheJoinTableInheritanceTest extends SecondLevelCacheAbstractTest
{
    public function testUseSameRegion()
    {
        $infoRegion     = $this->cache->getEntityCacheRegion(AttractionInfo::CLASSNAME);
        $contactRegion  = $this->cache->getEntityCacheRegion(AttractionContactInfo::CLASSNAME);
        $locationRegion = $this->cache->getEntityCacheRegion(AttractionLocationInfo::CLASSNAME);

        self::assertEquals($infoRegion->getName(), $contactRegion->getName());
        self::assertEquals($infoRegion->getName(), $locationRegion->getName());
    }

    public function testPutOnPersistJoinTableInheritance()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();
        $this->loadFixturesAttractionsInfo();

        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(AttractionInfo::CLASSNAME, $this->attractionsInfo[0]->getId()));
        self::assertTrue($this->cache->containsEntity(AttractionInfo::CLASSNAME, $this->attractionsInfo[1]->getId()));
        self::assertTrue($this->cache->containsEntity(AttractionInfo::CLASSNAME, $this->attractionsInfo[2]->getId()));
        self::assertTrue($this->cache->containsEntity(AttractionInfo::CLASSNAME, $this->attractionsInfo[3]->getId()));
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
            self::assertTrue($this->cache->containsEntity(AttractionInfo::CLASSNAME, $info->getId()));
            self::assertTrue($this->cache->containsEntity(get_class($info), $info->getId()));
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

        $this->cache->evictEntityRegion(AttractionInfo::CLASSNAME);

        $entityId1 = $this->attractionsInfo[0]->getId();
        $entityId2 = $this->attractionsInfo[1]->getId();

        self::assertFalse($this->cache->containsEntity(AttractionInfo::CLASSNAME, $entityId1));
        self::assertFalse($this->cache->containsEntity(AttractionInfo::CLASSNAME, $entityId2));
        self::assertFalse($this->cache->containsEntity(AttractionContactInfo::CLASSNAME, $entityId1));
        self::assertFalse($this->cache->containsEntity(AttractionContactInfo::CLASSNAME, $entityId2));

        $queryCount = $this->getCurrentQueryCount();
        $entity1    = $this->_em->find(AttractionInfo::CLASSNAME, $entityId1);
        $entity2    = $this->_em->find(AttractionInfo::CLASSNAME, $entityId2);

        //load entity and relation whit sub classes
        self::assertEquals($queryCount + 4, $this->getCurrentQueryCount());

        self::assertTrue($this->cache->containsEntity(AttractionInfo::CLASSNAME, $entityId1));
        self::assertTrue($this->cache->containsEntity(AttractionInfo::CLASSNAME, $entityId2));
        self::assertTrue($this->cache->containsEntity(AttractionContactInfo::CLASSNAME, $entityId1));
        self::assertTrue($this->cache->containsEntity(AttractionContactInfo::CLASSNAME, $entityId2));

        self::assertInstanceOf(AttractionInfo::CLASSNAME, $entity1);
        self::assertInstanceOf(AttractionInfo::CLASSNAME, $entity2);
        self::assertInstanceOf(AttractionContactInfo::CLASSNAME, $entity1);
        self::assertInstanceOf(AttractionContactInfo::CLASSNAME, $entity2);

        self::assertEquals($this->attractionsInfo[0]->getId(), $entity1->getId());
        self::assertEquals($this->attractionsInfo[0]->getFone(), $entity1->getFone());

        self::assertEquals($this->attractionsInfo[1]->getId(), $entity2->getId());
        self::assertEquals($this->attractionsInfo[1]->getFone(), $entity2->getFone());

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $entity3    = $this->_em->find(AttractionInfo::CLASSNAME, $entityId1);
        $entity4    = $this->_em->find(AttractionInfo::CLASSNAME, $entityId2);

        self::assertEquals($queryCount, $this->getCurrentQueryCount());

        self::assertInstanceOf(AttractionInfo::CLASSNAME, $entity3);
        self::assertInstanceOf(AttractionInfo::CLASSNAME, $entity4);
        self::assertInstanceOf(AttractionContactInfo::CLASSNAME, $entity3);
        self::assertInstanceOf(AttractionContactInfo::CLASSNAME, $entity4);

        self::assertNotSame($entity1, $entity3);
        self::assertEquals($entity1->getId(), $entity3->getId());
        self::assertEquals($entity1->getFone(), $entity3->getFone());

        self::assertNotSame($entity2, $entity4);
        self::assertEquals($entity2->getId(), $entity4->getId());
        self::assertEquals($entity2->getFone(), $entity4->getFone());
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

        self::assertCount(count($this->attractionsInfo), $result1);
        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->_em->clear();

        $result2 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        self::assertCount(count($this->attractionsInfo), $result2);
        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        foreach ($result2 as $entity) {
            self::assertInstanceOf(AttractionInfo::CLASSNAME, $entity);
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

        $entity = $this->_em->find(Attraction::CLASSNAME, $this->attractions[0]->getId());

        self::assertInstanceOf(Attraction::CLASSNAME, $entity);
        self::assertInstanceOf('Doctrine\ORM\PersistentCollection', $entity->getInfos());
        self::assertCount(1, $entity->getInfos());

        $ownerId    = $this->attractions[0]->getId();
        $queryCount = $this->getCurrentQueryCount();

        self::assertTrue($this->cache->containsEntity(Attraction::CLASSNAME, $ownerId));
        self::assertTrue($this->cache->containsCollection(Attraction::CLASSNAME, 'infos', $ownerId));

        self::assertInstanceOf(AttractionContactInfo::CLASSNAME, $entity->getInfos()->get(0));
        self::assertEquals($this->attractionsInfo[0]->getFone(), $entity->getInfos()->get(0)->getFone());

        $this->_em->clear();

        $entity = $this->_em->find(Attraction::CLASSNAME, $this->attractions[0]->getId());

        self::assertInstanceOf(Attraction::CLASSNAME, $entity);
        self::assertInstanceOf('Doctrine\ORM\PersistentCollection', $entity->getInfos());
        self::assertCount(1, $entity->getInfos());

        self::assertInstanceOf(AttractionContactInfo::CLASSNAME, $entity->getInfos()->get(0));
        self::assertEquals($this->attractionsInfo[0]->getFone(), $entity->getInfos()->get(0)->getFone());
    }
}