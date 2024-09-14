<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\Tests\Models;
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
    protected function setUp(): void
    {
        $this->useModelSet('vct_onetoone_compositeid');

        parent::setUp();

        $inversed               = new Entity\InversedOneToOneCompositeIdEntity();
        $inversed->id1          = 'abc';
        $inversed->id2          = 'def';
        $inversed->someProperty = 'some value to be loaded';

        $owning      = new Entity\OwningOneToOneCompositeIdEntity();
        $owning->id3 = 'ghi';

        $inversed->associatedEntity = $owning;
        $owning->associatedEntity   = $inversed;

        $this->_em->persist($inversed);
        $this->_em->persist($owning);

        $this->_em->flush();
        $this->_em->clear();
    }

    public static function tearDownAfterClass(): void
    {
        $conn = static::$sharedConn;

        $conn->executeStatement('DROP TABLE vct_owning_onetoone_compositeid');
        $conn->executeStatement('DROP TABLE vct_inversed_onetoone_compositeid');
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase(): void
    {
        $conn = $this->_em->getConnection();

        self::assertEquals('nop', $conn->fetchOne('SELECT id1 FROM vct_inversed_onetoone_compositeid LIMIT 1'));
        self::assertEquals('qrs', $conn->fetchOne('SELECT id2 FROM vct_inversed_onetoone_compositeid LIMIT 1'));

        self::assertEquals('tuv', $conn->fetchOne('SELECT id3 FROM vct_owning_onetoone_compositeid LIMIT 1'));
        self::assertEquals('nop', $conn->fetchOne('SELECT associated_id1 FROM vct_owning_onetoone_compositeid LIMIT 1'));
        self::assertEquals('qrs', $conn->fetchOne('SELECT associated_id2 FROM vct_owning_onetoone_compositeid LIMIT 1'));
    }

    /** @depends testThatTheValueOfIdentifiersAreConvertedInTheDatabase */
    public function testThatEntitiesAreFetchedFromTheDatabase(): void
    {
        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedOneToOneCompositeIdEntity::class,
            ['id1' => 'abc', 'id2' => 'def']
        );

        $owning = $this->_em->find(
            Models\ValueConversionType\OwningOneToOneCompositeIdEntity::class,
            'ghi'
        );

        self::assertInstanceOf(Models\ValueConversionType\InversedOneToOneCompositeIdEntity::class, $inversed);
        self::assertInstanceOf(Models\ValueConversionType\OwningOneToOneCompositeIdEntity::class, $owning);
    }

    /** @depends testThatEntitiesAreFetchedFromTheDatabase */
    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase(): void
    {
        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedOneToOneCompositeIdEntity::class,
            ['id1' => 'abc', 'id2' => 'def']
        );

        $owning = $this->_em->find(
            Models\ValueConversionType\OwningOneToOneCompositeIdEntity::class,
            'ghi'
        );

        self::assertEquals('abc', $inversed->id1);
        self::assertEquals('def', $inversed->id2);
        self::assertEquals('ghi', $owning->id3);
    }

    /** @depends testThatEntitiesAreFetchedFromTheDatabase */
    public function testThatTheProxyFromOwningToInversedIsLoaded(): void
    {
        $owning = $this->_em->find(
            Models\ValueConversionType\OwningOneToOneCompositeIdEntity::class,
            'ghi'
        );

        $inversedProxy = $owning->associatedEntity;

        self::assertEquals('some value to be loaded', $inversedProxy->someProperty);
    }

    /** @depends testThatEntitiesAreFetchedFromTheDatabase */
    public function testThatTheEntityFromInversedToOwningIsEagerLoaded(): void
    {
        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedOneToOneCompositeIdEntity::class,
            ['id1' => 'abc', 'id2' => 'def']
        );

        self::assertInstanceOf(Models\ValueConversionType\OwningOneToOneCompositeIdEntity::class, $inversed->associatedEntity);
    }
}
