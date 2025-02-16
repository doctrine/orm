<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\Models\Cache\Attraction;
use Doctrine\Tests\Models\Cache\AttractionContactInfo;
use Doctrine\Tests\Models\Cache\AttractionInfo;
use Doctrine\Tests\Models\Cache\AttractionLocationInfo;
use PHPUnit\Framework\Attributes\Group;

use function count;

#[Group('DDC-2183')]
class SecondLevelCacheJoinTableInheritanceTest extends SecondLevelCacheFunctionalTestCase
{
    public function testUseSameRegion(): void
    {
        $infoRegion     = $this->cache->getEntityCacheRegion(AttractionInfo::class);
        $contactRegion  = $this->cache->getEntityCacheRegion(AttractionContactInfo::class);
        $locationRegion = $this->cache->getEntityCacheRegion(AttractionLocationInfo::class);

        self::assertEquals($infoRegion->getName(), $contactRegion->getName());
        self::assertEquals($infoRegion->getName(), $locationRegion->getName());
    }

    public function testPutOnPersistJoinTableInheritance(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();
        $this->loadFixturesAttractionsInfo();

        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(AttractionInfo::class, $this->attractionsInfo[0]->getId()));
        self::assertTrue($this->cache->containsEntity(AttractionInfo::class, $this->attractionsInfo[1]->getId()));
        self::assertTrue($this->cache->containsEntity(AttractionInfo::class, $this->attractionsInfo[2]->getId()));
        self::assertTrue($this->cache->containsEntity(AttractionInfo::class, $this->attractionsInfo[3]->getId()));
    }

    public function testJoinTableCountaisRootClass(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();
        $this->loadFixturesAttractionsInfo();

        $this->_em->clear();

        foreach ($this->attractionsInfo as $info) {
            self::assertTrue($this->cache->containsEntity(AttractionInfo::class, $info->getId()));
            self::assertTrue($this->cache->containsEntity($info::class, $info->getId()));
        }
    }

    public function testPutAndLoadJoinTableEntities(): void
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

        self::assertFalse($this->cache->containsEntity(AttractionInfo::class, $entityId1));
        self::assertFalse($this->cache->containsEntity(AttractionInfo::class, $entityId2));
        self::assertFalse($this->cache->containsEntity(AttractionContactInfo::class, $entityId1));
        self::assertFalse($this->cache->containsEntity(AttractionContactInfo::class, $entityId2));

        $this->getQueryLog()->reset()->enable();
        $entity1 = $this->_em->find(AttractionInfo::class, $entityId1);
        $entity2 = $this->_em->find(AttractionInfo::class, $entityId2);

        //load entity and relation whit sub classes
        $this->assertQueryCount(4);

        self::assertTrue($this->cache->containsEntity(AttractionInfo::class, $entityId1));
        self::assertTrue($this->cache->containsEntity(AttractionInfo::class, $entityId2));
        self::assertTrue($this->cache->containsEntity(AttractionContactInfo::class, $entityId1));
        self::assertTrue($this->cache->containsEntity(AttractionContactInfo::class, $entityId2));

        self::assertInstanceOf(AttractionInfo::class, $entity1);
        self::assertInstanceOf(AttractionInfo::class, $entity2);
        self::assertInstanceOf(AttractionContactInfo::class, $entity1);
        self::assertInstanceOf(AttractionContactInfo::class, $entity2);

        self::assertEquals($this->attractionsInfo[0]->getId(), $entity1->getId());
        self::assertEquals($this->attractionsInfo[0]->getFone(), $entity1->getFone());

        self::assertEquals($this->attractionsInfo[1]->getId(), $entity2->getId());
        self::assertEquals($this->attractionsInfo[1]->getFone(), $entity2->getFone());

        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();
        $entity3 = $this->_em->find(AttractionInfo::class, $entityId1);
        $entity4 = $this->_em->find(AttractionInfo::class, $entityId2);

        $this->assertQueryCount(0);

        self::assertInstanceOf(AttractionInfo::class, $entity3);
        self::assertInstanceOf(AttractionInfo::class, $entity4);
        self::assertInstanceOf(AttractionContactInfo::class, $entity3);
        self::assertInstanceOf(AttractionContactInfo::class, $entity4);

        self::assertNotSame($entity1, $entity3);
        self::assertEquals($entity1->getId(), $entity3->getId());
        self::assertEquals($entity1->getFone(), $entity3->getFone());

        self::assertNotSame($entity2, $entity4);
        self::assertEquals($entity2->getId(), $entity4->getId());
        self::assertEquals($entity2->getFone(), $entity4->getFone());
    }

    public function testQueryCacheFindAllJoinTableEntities(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();
        $this->loadFixturesAttractionsInfo();
        $this->evictRegions();
        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();
        $dql     = 'SELECT i, a FROM Doctrine\Tests\Models\Cache\AttractionInfo i JOIN i.attraction a';
        $result1 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        self::assertCount(count($this->attractionsInfo), $result1);
        $this->assertQueryCount(1);

        $this->_em->clear();

        $result2 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        self::assertCount(count($this->attractionsInfo), $result2);
        $this->assertQueryCount(1);

        foreach ($result2 as $entity) {
            self::assertInstanceOf(AttractionInfo::class, $entity);
        }
    }

    public function testOneToManyRelationJoinTable(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();
        $this->loadFixturesAttractionsInfo();
        $this->evictRegions();
        $this->_em->clear();

        $entity = $this->_em->find(Attraction::class, $this->attractions[0]->getId());

        self::assertInstanceOf(Attraction::class, $entity);
        self::assertInstanceOf(PersistentCollection::class, $entity->getInfos());
        self::assertCount(1, $entity->getInfos());

        $ownerId = $this->attractions[0]->getId();

        self::assertTrue($this->cache->containsEntity(Attraction::class, $ownerId));
        self::assertTrue($this->cache->containsCollection(Attraction::class, 'infos', $ownerId));

        self::assertInstanceOf(AttractionContactInfo::class, $entity->getInfos()->get(0));
        self::assertEquals($this->attractionsInfo[0]->getFone(), $entity->getInfos()->get(0)->getFone());

        $this->_em->clear();

        $entity = $this->_em->find(Attraction::class, $this->attractions[0]->getId());

        self::assertInstanceOf(Attraction::class, $entity);
        self::assertInstanceOf(PersistentCollection::class, $entity->getInfos());
        self::assertCount(1, $entity->getInfos());

        self::assertInstanceOf(AttractionContactInfo::class, $entity->getInfos()->get(0));
        self::assertEquals($this->attractionsInfo[0]->getFone(), $entity->getInfos()->get(0)->getFone());
    }

    public function testQueryCacheShouldBeEvictedOnTimestampUpdate(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();
        $this->loadFixturesAttractionsInfo();
        $this->evictRegions();
        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();
        $dql = 'SELECT attractionInfo FROM Doctrine\Tests\Models\Cache\AttractionInfo attractionInfo';

        $result1 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        self::assertCount(count($this->attractionsInfo), $result1);
        $this->assertQueryCount(5);

        $contact = new AttractionContactInfo(
            '1234-1234',
            $this->_em->find(Attraction::class, $this->attractions[5]->getId()),
        );

        $this->_em->persist($contact);
        $this->_em->flush();
        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();

        $result2 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        self::assertCount(count($this->attractionsInfo) + 1, $result2);
        $this->assertQueryCount(6);

        foreach ($result2 as $entity) {
            self::assertInstanceOf(AttractionInfo::class, $entity);
        }
    }
}
