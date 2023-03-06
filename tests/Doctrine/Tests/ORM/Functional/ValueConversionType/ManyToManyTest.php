<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\Tests\Models\ValueConversionType as Entity;
use Doctrine\Tests\Models\ValueConversionType\InversedManyToManyEntity;
use Doctrine\Tests\Models\ValueConversionType\OwningManyToManyEntity;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;

/**
 * The entities all use a custom type that converst the value as identifier(s).
 * {@see \Doctrine\Tests\DbalTypes\Rot13Type}
 *
 * Test that ManyToMany associations work correctly.
 */
#[Group('DDC-3380')]
class ManyToManyTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('vct_manytomany');

        parent::setUp();

        $inversed      = new Entity\InversedManyToManyEntity();
        $inversed->id1 = 'abc';

        $owning      = new Entity\OwningManyToManyEntity();
        $owning->id2 = 'def';

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

        $conn->executeStatement('DROP TABLE vct_xref_manytomany');
        $conn->executeStatement('DROP TABLE vct_owning_manytomany');
        $conn->executeStatement('DROP TABLE vct_inversed_manytomany');
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase(): void
    {
        $conn = $this->_em->getConnection();

        self::assertEquals('nop', $conn->fetchOne('SELECT id1 FROM vct_inversed_manytomany LIMIT 1'));

        self::assertEquals('qrs', $conn->fetchOne('SELECT id2 FROM vct_owning_manytomany LIMIT 1'));

        self::assertEquals('nop', $conn->fetchOne('SELECT inversed_id FROM vct_xref_manytomany LIMIT 1'));
        self::assertEquals('qrs', $conn->fetchOne('SELECT owning_id FROM vct_xref_manytomany LIMIT 1'));
    }

    #[Depends('testThatTheValueOfIdentifiersAreConvertedInTheDatabase')]
    public function testThatEntitiesAreFetchedFromTheDatabase(): void
    {
        $inversed = $this->_em->find(
            InversedManyToManyEntity::class,
            'abc',
        );

        $owning = $this->_em->find(
            OwningManyToManyEntity::class,
            'def',
        );

        self::assertInstanceOf(InversedManyToManyEntity::class, $inversed);
        self::assertInstanceOf(OwningManyToManyEntity::class, $owning);
    }

    #[Depends('testThatEntitiesAreFetchedFromTheDatabase')]
    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase(): void
    {
        $inversed = $this->_em->find(
            InversedManyToManyEntity::class,
            'abc',
        );

        $owning = $this->_em->find(
            OwningManyToManyEntity::class,
            'def',
        );

        self::assertEquals('abc', $inversed->id1);
        self::assertEquals('def', $owning->id2);
    }

    #[Depends('testThatEntitiesAreFetchedFromTheDatabase')]
    public function testThatTheCollectionFromOwningToInversedIsLoaded(): void
    {
        $owning = $this->_em->find(
            OwningManyToManyEntity::class,
            'def',
        );

        self::assertCount(1, $owning->associatedEntities);
    }

    #[Depends('testThatEntitiesAreFetchedFromTheDatabase')]
    public function testThatTheCollectionFromInversedToOwningIsLoaded(): void
    {
        $inversed = $this->_em->find(
            InversedManyToManyEntity::class,
            'abc',
        );

        self::assertCount(1, $inversed->associatedEntities);
    }

    #[Depends('testThatTheCollectionFromOwningToInversedIsLoaded')]
    #[Depends('testThatTheCollectionFromInversedToOwningIsLoaded')]
    public function testThatTheJoinTableRowsAreRemovedWhenRemovingTheAssociation(): void
    {
        $conn = $this->_em->getConnection();

        // remove association

        $inversed = $this->_em->find(
            InversedManyToManyEntity::class,
            'abc',
        );

        foreach ($inversed->associatedEntities as $owning) {
            $inversed->associatedEntities->removeElement($owning);
            $owning->associatedEntities->removeElement($inversed);
        }

        $this->_em->flush();
        $this->_em->clear();

        // test association is removed

        self::assertEquals(0, $conn->fetchOne('SELECT COUNT(*) FROM vct_xref_manytomany'));
    }
}
