<?php

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\Tests\Models;
use Doctrine\Tests\Models\ValueConversionType as Entity;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * The entities all use a custom type that converst the value as identifier(s).
 * {@see \Doctrine\Tests\DbalTypes\Rot13Type}
 *
 * Test that ManyToMany associations work correctly.
 *
 * @group DDC-3380
 */
class ManyToManyTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('vct_manytomany');

        parent::setUp();

        $inversed = new Entity\InversedManyToManyEntity();
        $inversed->id1 = 'abc';

        $owning = new Entity\OwningManyToManyEntity();
        $owning->id2 = 'def';

        $inversed->associatedEntities->add($owning);
        $owning->associatedEntities->add($inversed);

        $this->em->persist($inversed);
        $this->em->persist($owning);

        $this->em->flush();
        $this->em->clear();
    }

    public static function tearDownAfterClass()
    {
        $conn = static::$sharedConn;

        $conn->executeUpdate('DROP TABLE vct_xref_manytomany');
        $conn->executeUpdate('DROP TABLE vct_owning_manytomany');
        $conn->executeUpdate('DROP TABLE vct_inversed_manytomany');
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase()
    {
        $conn = $this->em->getConnection();

        self::assertEquals('nop', $conn->fetchColumn('SELECT id1 FROM vct_inversed_manytomany LIMIT 1'));

        self::assertEquals('qrs', $conn->fetchColumn('SELECT id2 FROM vct_owning_manytomany LIMIT 1'));

        self::assertEquals('nop', $conn->fetchColumn('SELECT inversed_id FROM vct_xref_manytomany LIMIT 1'));
        self::assertEquals('qrs', $conn->fetchColumn('SELECT owning_id FROM vct_xref_manytomany LIMIT 1'));
    }

    /**
     * @depends testThatTheValueOfIdentifiersAreConvertedInTheDatabase
     */
    public function testThatEntitiesAreFetchedFromTheDatabase()
    {
        $inversed = $this->em->find(
            Models\ValueConversionType\InversedManyToManyEntity::class,
            'abc'
        );

        $owning = $this->em->find(
            Models\ValueConversionType\OwningManyToManyEntity::class,
            'def'
        );

        self::assertInstanceOf(Models\ValueConversionType\InversedManyToManyEntity::class, $inversed);
        self::assertInstanceOf(Models\ValueConversionType\OwningManyToManyEntity::class, $owning);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase()
    {
        $inversed = $this->em->find(
            Models\ValueConversionType\InversedManyToManyEntity::class,
            'abc'
        );

        $owning = $this->em->find(
            Models\ValueConversionType\OwningManyToManyEntity::class,
            'def'
        );

        self::assertEquals('abc', $inversed->id1);
        self::assertEquals('def', $owning->id2);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheCollectionFromOwningToInversedIsLoaded()
    {
        $owning = $this->em->find(
            Models\ValueConversionType\OwningManyToManyEntity::class,
            'def'
        );

        self::assertCount(1, $owning->associatedEntities);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheCollectionFromInversedToOwningIsLoaded()
    {
        $inversed = $this->em->find(
            Models\ValueConversionType\InversedManyToManyEntity::class,
            'abc'
        );

        self::assertCount(1, $inversed->associatedEntities);
    }

    /**
     * @depends testThatTheCollectionFromOwningToInversedIsLoaded
     * @depends testThatTheCollectionFromInversedToOwningIsLoaded
     */
    public function testThatTheJoinTableRowsAreRemovedWhenRemovingTheAssociation()
    {
        $conn = $this->em->getConnection();

        // remove association

        $inversed = $this->em->find(
            Models\ValueConversionType\InversedManyToManyEntity::class,
            'abc'
        );

        foreach ($inversed->associatedEntities as $owning) {
            $inversed->associatedEntities->removeElement($owning);
            $owning->associatedEntities->removeElement($inversed);
        }

        $this->em->flush();
        $this->em->clear();

        // test association is removed

        self::assertEquals(0, $conn->fetchColumn('SELECT COUNT(*) FROM vct_xref_manytomany'));
    }
}
