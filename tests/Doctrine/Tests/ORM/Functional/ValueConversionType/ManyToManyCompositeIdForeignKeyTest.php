<?php

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\Tests\Models\ValueConversionType as Entity;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * The entities all use a custom type that converst the value as identifier(s).
 * {@see \Doctrine\Tests\DbalTypes\Rot13Type}
 *
 * Test that ManyToMany associations with composite id of which one is a
 * association itself work correctly.
 *
 * @group DDC-3380
 */
class ManyToManyCompositeIdForeignKeyTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('vct_manytomany_compositeid_foreignkey');

        parent::setUp();

        $auxiliary = new Entity\AuxiliaryEntity();
        $auxiliary->id4 = 'abc';

        $inversed = new Entity\InversedManyToManyCompositeIdForeignKeyEntity();
        $inversed->id1 = 'def';
        $inversed->foreignEntity = $auxiliary;

        $owning = new Entity\OwningManyToManyCompositeIdForeignKeyEntity();
        $owning->id2 = 'ghi';

        $inversed->associatedEntities->add($owning);
        $owning->associatedEntities->add($inversed);

        $this->_em->persist($auxiliary);
        $this->_em->persist($inversed);
        $this->_em->persist($owning);

        $this->_em->flush();
        $this->_em->clear();
    }

    public static function tearDownAfterClass()
    {
        $conn = static::$_sharedConn;

        $conn->executeUpdate('DROP TABLE vct_xref_manytomany_compositeid_foreignkey');
        $conn->executeUpdate('DROP TABLE vct_owning_manytomany_compositeid_foreignkey');
        $conn->executeUpdate('DROP TABLE vct_inversed_manytomany_compositeid_foreignkey');
        $conn->executeUpdate('DROP TABLE vct_auxiliary');
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase()
    {
        $conn = $this->_em->getConnection();

        $this->assertEquals('nop', $conn->fetchColumn('SELECT id4 FROM vct_auxiliary LIMIT 1'));

        $this->assertEquals('qrs', $conn->fetchColumn('SELECT id1 FROM vct_inversed_manytomany_compositeid_foreignkey LIMIT 1'));
        $this->assertEquals('nop', $conn->fetchColumn('SELECT foreign_id FROM vct_inversed_manytomany_compositeid_foreignkey LIMIT 1'));

        $this->assertEquals('tuv', $conn->fetchColumn('SELECT id2 FROM vct_owning_manytomany_compositeid_foreignkey LIMIT 1'));

        $this->assertEquals('qrs', $conn->fetchColumn('SELECT associated_id FROM vct_xref_manytomany_compositeid_foreignkey LIMIT 1'));
        $this->assertEquals('nop', $conn->fetchColumn('SELECT associated_foreign_id FROM vct_xref_manytomany_compositeid_foreignkey LIMIT 1'));
        $this->assertEquals('tuv', $conn->fetchColumn('SELECT owning_id FROM vct_xref_manytomany_compositeid_foreignkey LIMIT 1'));
    }

    /**
     * @depends testThatTheValueOfIdentifiersAreConvertedInTheDatabase
     */
    public function testThatEntitiesAreFetchedFromTheDatabase()
    {
        $auxiliary = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\AuxiliaryEntity',
            'abc'
        );

        $inversed = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\InversedManyToManyCompositeIdForeignKeyEntity',
            array('id1' => 'def', 'foreignEntity' => 'abc')
        );

        $owning = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToManyCompositeIdForeignKeyEntity',
            'ghi'
        );

        $this->assertInstanceOf('Doctrine\Tests\Models\ValueConversionType\AuxiliaryEntity', $auxiliary);
        $this->assertInstanceOf('Doctrine\Tests\Models\ValueConversionType\InversedManyToManyCompositeIdForeignKeyEntity', $inversed);
        $this->assertInstanceOf('Doctrine\Tests\Models\ValueConversionType\OwningManyToManyCompositeIdForeignKeyEntity', $owning);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase()
    {
        $auxiliary = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\AuxiliaryEntity',
            'abc'
        );

        $inversed = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\InversedManyToManyCompositeIdForeignKeyEntity',
            array('id1' => 'def', 'foreignEntity' => 'abc')
        );

        $owning = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToManyCompositeIdForeignKeyEntity',
            'ghi'
        );

        $this->assertEquals('abc', $auxiliary->id4);
        $this->assertEquals('def', $inversed->id1);
        $this->assertEquals('abc', $inversed->foreignEntity->id4);
        $this->assertEquals('ghi', $owning->id2);
    }

    /**
     * @depends testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase
     */
    public function testThatInversedEntityIsFetchedFromTheDatabaseUsingAuxiliaryEntityAsId()
    {
        $auxiliary = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\AuxiliaryEntity',
            'abc'
        );

        $inversed = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\InversedManyToManyCompositeIdForeignKeyEntity',
            array('id1' => 'def', 'foreignEntity' => $auxiliary)
        );

        $this->assertInstanceOf('Doctrine\Tests\Models\ValueConversionType\InversedManyToManyCompositeIdForeignKeyEntity', $inversed);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheCollectionFromOwningToInversedIsLoaded()
    {
        $owning = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToManyCompositeIdForeignKeyEntity',
            'ghi'
        );

        $this->assertCount(1, $owning->associatedEntities);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheCollectionFromInversedToOwningIsLoaded()
    {
        $inversed = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\InversedManyToManyCompositeIdForeignKeyEntity',
            array('id1' => 'def', 'foreignEntity' => 'abc')
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
            'Doctrine\Tests\Models\ValueConversionType\InversedManyToManyCompositeIdForeignKeyEntity',
            array('id1' => 'def', 'foreignEntity' => 'abc')
        );

        foreach ($inversed->associatedEntities as $owning) {
            $inversed->associatedEntities->removeElement($owning);
            $owning->associatedEntities->removeElement($inversed);
        }

        $this->_em->flush();
        $this->_em->clear();

        // test association is removed

        $this->assertEquals(0, $conn->fetchColumn('SELECT COUNT(*) FROM vct_xref_manytomany_compositeid_foreignkey'));
    }
}
