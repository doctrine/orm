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
 * Test that ManyToMany associations with composite id work correctly.
 *
 * @group DDC-3380
 */
class ManyToManyCompositeIdTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('vct_manytomany_compositeid');

        parent::setUp();

        $inversed      = new Entity\InversedManyToManyCompositeIdEntity();
        $inversed->id1 = 'abc';
        $inversed->id2 = 'def';

        $owning      = new Entity\OwningManyToManyCompositeIdEntity();
        $owning->id3 = 'ghi';

        $inversed->associatedEntities->add($owning);
        $owning->associatedEntities->add($inversed);

        $this->_em->persist($inversed);
        $this->_em->persist($owning);

        $this->_em->flush();
        $this->_em->clear();
    }

    public static function tearDownAfterClass(): void
    {
        $conn = static::$sharedConn;

        $conn->executeStatement('DROP TABLE vct_xref_manytomany_compositeid');
        $conn->executeStatement('DROP TABLE vct_owning_manytomany_compositeid');
        $conn->executeStatement('DROP TABLE vct_inversed_manytomany_compositeid');
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase(): void
    {
        $conn = $this->_em->getConnection();

        self::assertEquals('nop', $conn->fetchOne('SELECT id1 FROM vct_inversed_manytomany_compositeid LIMIT 1'));
        self::assertEquals('qrs', $conn->fetchOne('SELECT id2 FROM vct_inversed_manytomany_compositeid LIMIT 1'));

        self::assertEquals('tuv', $conn->fetchOne('SELECT id3 FROM vct_owning_manytomany_compositeid LIMIT 1'));

        self::assertEquals('nop', $conn->fetchOne('SELECT inversed_id1 FROM vct_xref_manytomany_compositeid LIMIT 1'));
        self::assertEquals('qrs', $conn->fetchOne('SELECT inversed_id2 FROM vct_xref_manytomany_compositeid LIMIT 1'));
        self::assertEquals('tuv', $conn->fetchOne('SELECT owning_id FROM vct_xref_manytomany_compositeid LIMIT 1'));
    }

    /** @depends testThatTheValueOfIdentifiersAreConvertedInTheDatabase */
    public function testThatEntitiesAreFetchedFromTheDatabase(): void
    {
        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedManyToManyCompositeIdEntity::class,
            ['id1' => 'abc', 'id2' => 'def']
        );

        $owning = $this->_em->find(
            Models\ValueConversionType\OwningManyToManyCompositeIdEntity::class,
            'ghi'
        );

        self::assertInstanceOf(Models\ValueConversionType\InversedManyToManyCompositeIdEntity::class, $inversed);
        self::assertInstanceOf(Models\ValueConversionType\OwningManyToManyCompositeIdEntity::class, $owning);
    }

    /** @depends testThatEntitiesAreFetchedFromTheDatabase */
    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase(): void
    {
        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedManyToManyCompositeIdEntity::class,
            ['id1' => 'abc', 'id2' => 'def']
        );

        $owning = $this->_em->find(
            Models\ValueConversionType\OwningManyToManyCompositeIdEntity::class,
            'ghi'
        );

        self::assertEquals('abc', $inversed->id1);
        self::assertEquals('def', $inversed->id2);
        self::assertEquals('ghi', $owning->id3);
    }

    /** @depends testThatEntitiesAreFetchedFromTheDatabase */
    public function testThatTheCollectionFromOwningToInversedIsLoaded(): void
    {
        $owning = $this->_em->find(
            Models\ValueConversionType\OwningManyToManyCompositeIdEntity::class,
            'ghi'
        );

        self::assertCount(1, $owning->associatedEntities);
    }

    /** @depends testThatEntitiesAreFetchedFromTheDatabase */
    public function testThatTheCollectionFromInversedToOwningIsLoaded(): void
    {
        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedManyToManyCompositeIdEntity::class,
            ['id1' => 'abc', 'id2' => 'def']
        );

        self::assertCount(1, $inversed->associatedEntities);
    }

    /**
     * @depends testThatTheCollectionFromOwningToInversedIsLoaded
     * @depends testThatTheCollectionFromInversedToOwningIsLoaded
     */
    public function testThatTheJoinTableRowsAreRemovedWhenRemovingTheAssociation(): void
    {
        $conn = $this->_em->getConnection();

        // remove association

        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedManyToManyCompositeIdEntity::class,
            ['id1' => 'abc', 'id2' => 'def']
        );

        foreach ($inversed->associatedEntities as $owning) {
            $inversed->associatedEntities->removeElement($owning);
            $owning->associatedEntities->removeElement($inversed);
        }

        $this->_em->flush();
        $this->_em->clear();

        // test association is removed

        self::assertEquals(0, $conn->fetchOne('SELECT COUNT(*) FROM vct_xref_manytomany_compositeid'));
    }
}
