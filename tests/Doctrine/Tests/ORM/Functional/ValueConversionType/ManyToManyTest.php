<?php

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\DBAL\Types\Type as DBALType;
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

        $this->_em->persist($inversed);
        $this->_em->persist($owning);

        $this->_em->flush();
        $this->_em->clear();
    }

    public static function tearDownAfterClass()
    {
        $conn = static::$_sharedConn;

        $conn->executeUpdate('DROP TABLE vct_xref_manytomany');
        $conn->executeUpdate('DROP TABLE vct_owning_manytomany');
        $conn->executeUpdate('DROP TABLE vct_inversed_manytomany');
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase()
    {
        $conn = $this->_em->getConnection();

        $this->assertEquals('nop', $conn->fetchColumn('SELECT id1 FROM vct_inversed_manytomany LIMIT 1'));

        $this->assertEquals('qrs', $conn->fetchColumn('SELECT id2 FROM vct_owning_manytomany LIMIT 1'));

        $this->assertEquals('nop', $conn->fetchColumn('SELECT inversed_id FROM vct_xref_manytomany LIMIT 1'));
        $this->assertEquals('qrs', $conn->fetchColumn('SELECT owning_id FROM vct_xref_manytomany LIMIT 1'));
    }

    /**
     * @depends testThatTheValueOfIdentifiersAreConvertedInTheDatabase
     */
    public function testThatEntitiesAreFetchedFromTheDatabase()
    {
        $inversed = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\InversedManyToManyEntity',
            'abc'
        );

        $owning = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToManyEntity',
            'def'
        );

        $this->assertInstanceOf('Doctrine\Tests\Models\ValueConversionType\InversedManyToManyEntity', $inversed);
        $this->assertInstanceOf('Doctrine\Tests\Models\ValueConversionType\OwningManyToManyEntity', $owning);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase()
    {
        $inversed = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\InversedManyToManyEntity',
            'abc'
        );

        $owning = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToManyEntity',
            'def'
        );

        $this->assertEquals('abc', $inversed->id1);
        $this->assertEquals('def', $owning->id2);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheCollectionFromOwningToInversedIsLoaded()
    {
        $owning = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToManyEntity',
            'def'
        );

        $this->assertCount(1, $owning->associatedEntities);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheCollectionFromInversedToOwningIsLoaded()
    {
        $inversed = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\InversedManyToManyEntity',
            'abc'
        );

        $this->assertCount(1, $inversed->associatedEntities);
    }

    /**
     * @depends testThatTheCollectionFromOwningToInversedIsLoaded
     * @depends testThatTheCollectionFromInversedToOwningIsLoaded
     */
    public function testThatTheJoinTableRowsAreRemovedWhenRemovingTheAssociation()
    {
        $conn = $this->_em->getConnection();

        // remove association

        $inversed = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\InversedManyToManyEntity',
            'abc'
        );

        foreach ($inversed->associatedEntities as $owning) {
            $inversed->associatedEntities->removeElement($owning);
            $owning->associatedEntities->removeElement($inversed);
        }

        $this->_em->flush();
        $this->_em->clear();

        // test association is removed

        $this->assertEquals(0, $conn->fetchColumn('SELECT COUNT(*) FROM vct_xref_manytomany'));
    }
}
