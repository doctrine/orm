<?php

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\Tests\Models\ValueConversionType as Entity;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * The entities all use a custom type that converst the value as identifier(s).
 * {@see \Doctrine\Tests\DbalTypes\Rot13Type}
 *
 * Test that OneToOne associations with composite id work correctly.
 *
 * @group DDC-3380
 */
class OneToOneCompositeIdTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('vct_onetoone_compositeid');
        parent::setUp();

        $inversed = new Entity\InversedOneToOneCompositeIdEntity();
        $inversed->id1 = 'abc';
        $inversed->id2 = 'def';
        $inversed->someProperty = 'some value to be loaded';

        $owning = new Entity\OwningOneToOneCompositeIdEntity();
        $owning->id3 = 'ghi';

        $inversed->associatedEntity = $owning;
        $owning->associatedEntity = $inversed;

        $this->_em->persist($inversed);
        $this->_em->persist($owning);

        $this->_em->flush();
        $this->_em->clear();
    }

    public static function tearDownAfterClass()
    {
        $conn = static::$_sharedConn;

        $conn->executeUpdate('DROP TABLE vct_owning_onetoone_compositeid');
        $conn->executeUpdate('DROP TABLE vct_inversed_onetoone_compositeid');
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase()
    {
        $conn = $this->_em->getConnection();

        self::assertEquals('nop', $conn->fetchColumn('SELECT id1 FROM vct_inversed_onetoone_compositeid LIMIT 1'));
        self::assertEquals('qrs', $conn->fetchColumn('SELECT id2 FROM vct_inversed_onetoone_compositeid LIMIT 1'));

        self::assertEquals('tuv', $conn->fetchColumn('SELECT id3 FROM vct_owning_onetoone_compositeid LIMIT 1'));
        self::assertEquals('nop', $conn->fetchColumn('SELECT associated_id1 FROM vct_owning_onetoone_compositeid LIMIT 1'));
        self::assertEquals('qrs', $conn->fetchColumn('SELECT associated_id2 FROM vct_owning_onetoone_compositeid LIMIT 1'));
    }

    /**
     * @depends testThatTheValueOfIdentifiersAreConvertedInTheDatabase
     */
    public function testThatEntitiesAreFetchedFromTheDatabase()
    {
        $inversed = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\InversedOneToOneCompositeIdEntity',
            array('id1' => 'abc', 'id2' => 'def')
        );

        $owning = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\OwningOneToOneCompositeIdEntity',
            'ghi'
        );

        self::assertInstanceOf('Doctrine\Tests\Models\ValueConversionType\InversedOneToOneCompositeIdEntity', $inversed);
        self::assertInstanceOf('Doctrine\Tests\Models\ValueConversionType\OwningOneToOneCompositeIdEntity', $owning);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase()
    {
        $inversed = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\InversedOneToOneCompositeIdEntity',
            array('id1' => 'abc', 'id2' => 'def')
        );

        $owning = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\OwningOneToOneCompositeIdEntity',
            'ghi'
        );

        self::assertEquals('abc', $inversed->id1);
        self::assertEquals('def', $inversed->id2);
        self::assertEquals('ghi', $owning->id3);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheProxyFromOwningToInversedIsLoaded()
    {
        $owning = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\OwningOneToOneCompositeIdEntity',
            'ghi'
        );

        $inversedProxy = $owning->associatedEntity;

        self::assertEquals('some value to be loaded', $inversedProxy->someProperty);
    }

    /**
     * @depends testThatEntitiesAreFetchedFromTheDatabase
     */
    public function testThatTheEntityFromInversedToOwningIsEagerLoaded()
    {
        $inversed = $this->_em->find(
            'Doctrine\Tests\Models\ValueConversionType\InversedOneToOneCompositeIdEntity',
            array('id1' => 'abc', 'id2' => 'def')
        );

        self::assertInstanceOf('Doctrine\Tests\Models\ValueConversionType\OwningOneToOneCompositeIdEntity', $inversed->associatedEntity);
    }
}
