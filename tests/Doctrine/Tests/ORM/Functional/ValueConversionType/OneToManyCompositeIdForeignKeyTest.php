<?php

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\Tests\Models\ValueConversionType as Entity;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * The entities all use a custom type that converst the value as identifier(s).
 * {@see \Doctrine\Tests\DbalTypes\Rot13Type}
 *
 * Test that OneToMany associations with composite id of which one is a
 * association itself work correctly.
 *
 * @group DDC-3380
 */
class OneToManyCompositeIdForeignKeyTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        if (DBALType::hasType('rot13')) {
            DBALType::overrideType('rot13', 'Doctrine\Tests\DbalTypes\Rot13Type');
        } else {
            DBALType::addType('rot13', 'Doctrine\Tests\DbalTypes\Rot13Type');
        }

        $this->useModelSet('vct_onetomany_compositeid_foreignkey');
        parent::setUp();

        $auxiliary = new Entity\AuxiliaryEntity();
        $auxiliary->id = 'abc';

        $inversed = new Entity\InversedOneToManyCompositeIdForeignKeyEntity();
        $inversed->id = 'def';
        $inversed->foreignEntity = $auxiliary;
        $inversed->someProperty = 'some value to be loaded';

        $owning = new Entity\OwningManyToOneCompositeIdForeignKeyEntity();
        $owning->id = 'ghi';

        $inversed->associatedEntities->add($owning);
        $owning->associatedEntity = $inversed;

        $this->_em->persist($auxiliary);
        $this->_em->persist($inversed);
        $this->_em->persist($owning);

        $this->_em->flush();
        $this->_em->clear();
    }

    public static function tearDownAfterClass()
    {
        $conn = static::$_sharedConn;

        $conn->executeUpdate('DROP TABLE vct_owning_manytoone_compositeid_foreignkey');
        $conn->executeUpdate('DROP TABLE vct_inversed_onetomany_compositeid_foreignkey');
        $conn->executeUpdate('DROP TABLE vct_auxiliary');
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase()
    {
        $conn = $this->_em->getConnection();

        $this->assertEquals('qrs', $conn->fetchColumn('SELECT id FROM vct_inversed_onetomany_compositeid_foreignkey LIMIT 1'));
        $this->assertEquals('nop', $conn->fetchColumn('SELECT foreign_id FROM vct_inversed_onetomany_compositeid_foreignkey LIMIT 1'));

        $this->assertEquals('tuv', $conn->fetchColumn('SELECT id FROM vct_owning_manytoone_compositeid_foreignkey LIMIT 1'));
        $this->assertEquals('qrs', $conn->fetchColumn('SELECT associated_id FROM vct_owning_manytoone_compositeid_foreignkey LIMIT 1'));
        $this->assertEquals('nop', $conn->fetchColumn('SELECT associated_foreign_id FROM vct_owning_manytoone_compositeid_foreignkey LIMIT 1'));
    }

    /**
     * @depends testThatTheValueOfIdentifiersAreConvertedInTheDatabase
     */
    public function testThatEntitiesAreFetchedFromTheDatabase()
    {
        $inversed = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\InversedOneToManyCompositeIdForeignKeyEntity',
            array('id' => 'def', 'foreignEntity' => 'abc')
        );

        $owning = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToOneCompositeIdForeignKeyEntity',
            'ghi'
        );

        $this->assertInstanceOf('Doctrine\Tests\Models\ValueConversionType\InversedOneToManyCompositeIdForeignKeyEntity', $inversed);
        $this->assertInstanceOf('Doctrine\Tests\Models\ValueConversionType\OwningManyToOneCompositeIdForeignKeyEntity', $owning);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase()
    {
        $inversed = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\InversedOneToManyCompositeIdForeignKeyEntity',
            array('id' => 'def', 'foreignEntity' => 'abc')
        );

        $owning = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToOneCompositeIdForeignKeyEntity',
            'ghi'
        );

        $this->assertEquals('def', $inversed->id);
        $this->assertEquals('abc', $inversed->foreignEntity->id);
        $this->assertEquals('ghi', $owning->id);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheProxyFromOwningToInversedIsLoaded()
    {
        $owning = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToOneCompositeIdForeignKeyEntity',
            'ghi'
        );

        $inversedProxy = $owning->associatedEntity;

        $this->assertEquals('some value to be loaded', $inversedProxy->someProperty);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheCollectionFromInversedToOwningIsLoaded()
    {
        $inversed = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\InversedOneToManyCompositeIdForeignKeyEntity',
            array('id' => 'def', 'foreignEntity' => 'abc')
        );

        $this->assertCount(1, $inversed->associatedEntities);
    }
}
