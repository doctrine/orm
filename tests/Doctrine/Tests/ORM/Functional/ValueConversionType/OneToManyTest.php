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
 * Test that OneToMany associations work correctly.
 *
 * @group DDC-3380
 */
class OneToManyTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('vct_onetomany');

        parent::setUp();

        $inversed               = new Entity\InversedOneToManyEntity();
        $inversed->id1          = 'abc';
        $inversed->someProperty = 'some value to be loaded';

        $owning      = new Entity\OwningManyToOneEntity();
        $owning->id2 = 'def';

        $inversed->associatedEntities->add($owning);
        $owning->associatedEntity = $inversed;

        $this->_em->persist($inversed);
        $this->_em->persist($owning);

        $this->_em->flush();
        $this->_em->clear();
    }

    public static function tearDownAfterClass(): void
    {
        $conn = static::$sharedConn;

        $conn->executeStatement('DROP TABLE vct_owning_manytoone');
        $conn->executeStatement('DROP TABLE vct_inversed_onetomany');
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase(): void
    {
        $conn = $this->_em->getConnection();

        self::assertEquals('nop', $conn->fetchOne('SELECT id1 FROM vct_inversed_onetomany LIMIT 1'));

        self::assertEquals('qrs', $conn->fetchOne('SELECT id2 FROM vct_owning_manytoone LIMIT 1'));
        self::assertEquals('nop', $conn->fetchOne('SELECT associated_id FROM vct_owning_manytoone LIMIT 1'));
    }

    /** @depends testThatTheValueOfIdentifiersAreConvertedInTheDatabase */
    public function testThatEntitiesAreFetchedFromTheDatabase(): void
    {
        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedOneToManyEntity::class,
            'abc'
        );

        $owning = $this->_em->find(
            Models\ValueConversionType\OwningManyToOneEntity::class,
            'def'
        );

        self::assertInstanceOf(Models\ValueConversionType\InversedOneToManyEntity::class, $inversed);
        self::assertInstanceOf(Models\ValueConversionType\OwningManyToOneEntity::class, $owning);
    }

    /** @depends testThatEntitiesAreFetchedFromTheDatabase */
    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase(): void
    {
        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedOneToManyEntity::class,
            'abc'
        );

        $owning = $this->_em->find(
            Models\ValueConversionType\OwningManyToOneEntity::class,
            'def'
        );

        self::assertEquals('abc', $inversed->id1);
        self::assertEquals('def', $owning->id2);
    }

    /** @depends testThatEntitiesAreFetchedFromTheDatabase */
    public function testThatTheProxyFromOwningToInversedIsLoaded(): void
    {
        $owning = $this->_em->find(
            Models\ValueConversionType\OwningManyToOneEntity::class,
            'def'
        );

        $inversedProxy = $owning->associatedEntity;

        self::assertEquals('some value to be loaded', $inversedProxy->someProperty);
    }

    /** @depends testThatEntitiesAreFetchedFromTheDatabase */
    public function testThatTheCollectionFromInversedToOwningIsLoaded(): void
    {
        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedOneToManyEntity::class,
            'abc'
        );

        self::assertCount(1, $inversed->associatedEntities);
    }
}
