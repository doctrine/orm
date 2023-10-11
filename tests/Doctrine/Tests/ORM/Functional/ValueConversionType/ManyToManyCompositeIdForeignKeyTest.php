<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\Tests\Models\ValueConversionType as Entity;
use Doctrine\Tests\Models\ValueConversionType\AuxiliaryEntity;
use Doctrine\Tests\Models\ValueConversionType\InversedManyToManyCompositeIdForeignKeyEntity;
use Doctrine\Tests\Models\ValueConversionType\OwningManyToManyCompositeIdForeignKeyEntity;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;

/**
 * The entities all use a custom type that converst the value as identifier(s).
 * {@see \Doctrine\Tests\DbalTypes\Rot13Type}
 *
 * Test that ManyToMany associations with composite id of which one is a
 * association itself work correctly.
 */
#[Group('DDC-3380')]
class ManyToManyCompositeIdForeignKeyTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('vct_manytomany_compositeid_foreignkey');

        parent::setUp();

        $auxiliary      = new Entity\AuxiliaryEntity();
        $auxiliary->id4 = 'abc';

        $inversed                = new Entity\InversedManyToManyCompositeIdForeignKeyEntity();
        $inversed->id1           = 'def';
        $inversed->foreignEntity = $auxiliary;

        $owning      = new Entity\OwningManyToManyCompositeIdForeignKeyEntity();
        $owning->id2 = 'ghi';

        $inversed->associatedEntities->add($owning);
        $owning->associatedEntities->add($inversed);

        $this->_em->persist($auxiliary);
        $this->_em->persist($inversed);
        $this->_em->persist($owning);

        $this->_em->flush();
        $this->_em->clear();
    }

    public static function tearDownAfterClass(): void
    {
        $conn = static::$sharedConn;

        $conn->executeStatement('DROP TABLE vct_xref_manytomany_compositeid_foreignkey');
        $conn->executeStatement('DROP TABLE vct_owning_manytomany_compositeid_foreignkey');
        $conn->executeStatement('DROP TABLE vct_inversed_manytomany_compositeid_foreignkey');
        $conn->executeStatement('DROP TABLE vct_auxiliary');
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase(): void
    {
        $conn = $this->_em->getConnection();

        self::assertEquals('nop', $conn->fetchOne('SELECT id4 FROM vct_auxiliary LIMIT 1'));

        self::assertEquals('qrs', $conn->fetchOne('SELECT id1 FROM vct_inversed_manytomany_compositeid_foreignkey LIMIT 1'));
        self::assertEquals('nop', $conn->fetchOne('SELECT foreign_id FROM vct_inversed_manytomany_compositeid_foreignkey LIMIT 1'));

        self::assertEquals('tuv', $conn->fetchOne('SELECT id2 FROM vct_owning_manytomany_compositeid_foreignkey LIMIT 1'));

        self::assertEquals('qrs', $conn->fetchOne('SELECT associated_id FROM vct_xref_manytomany_compositeid_foreignkey LIMIT 1'));
        self::assertEquals('nop', $conn->fetchOne('SELECT associated_foreign_id FROM vct_xref_manytomany_compositeid_foreignkey LIMIT 1'));
        self::assertEquals('tuv', $conn->fetchOne('SELECT owning_id FROM vct_xref_manytomany_compositeid_foreignkey LIMIT 1'));
    }

    #[Depends('testThatTheValueOfIdentifiersAreConvertedInTheDatabase')]
    public function testThatEntitiesAreFetchedFromTheDatabase(): void
    {
        $auxiliary = $this->_em->find(
            AuxiliaryEntity::class,
            'abc',
        );

        $inversed = $this->_em->find(
            InversedManyToManyCompositeIdForeignKeyEntity::class,
            ['id1' => 'def', 'foreignEntity' => 'abc'],
        );

        $owning = $this->_em->find(
            OwningManyToManyCompositeIdForeignKeyEntity::class,
            'ghi',
        );

        self::assertInstanceOf(AuxiliaryEntity::class, $auxiliary);
        self::assertInstanceOf(InversedManyToManyCompositeIdForeignKeyEntity::class, $inversed);
        self::assertInstanceOf(OwningManyToManyCompositeIdForeignKeyEntity::class, $owning);
    }

    #[Depends('testThatEntitiesAreFetchedFromTheDatabase')]
    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase(): void
    {
        $auxiliary = $this->_em->find(
            AuxiliaryEntity::class,
            'abc',
        );

        $inversed = $this->_em->find(
            InversedManyToManyCompositeIdForeignKeyEntity::class,
            ['id1' => 'def', 'foreignEntity' => 'abc'],
        );

        $owning = $this->_em->find(
            OwningManyToManyCompositeIdForeignKeyEntity::class,
            'ghi',
        );

        self::assertEquals('abc', $auxiliary->id4);
        self::assertEquals('def', $inversed->id1);
        self::assertEquals('abc', $inversed->foreignEntity->id4);
        self::assertEquals('ghi', $owning->id2);
    }

    #[Depends('testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase')]
    public function testThatInversedEntityIsFetchedFromTheDatabaseUsingAuxiliaryEntityAsId(): void
    {
        $auxiliary = $this->_em->find(
            AuxiliaryEntity::class,
            'abc',
        );

        $inversed = $this->_em->find(
            InversedManyToManyCompositeIdForeignKeyEntity::class,
            ['id1' => 'def', 'foreignEntity' => $auxiliary],
        );

        self::assertInstanceOf(InversedManyToManyCompositeIdForeignKeyEntity::class, $inversed);
    }

    #[Depends('testThatEntitiesAreFetchedFromTheDatabase')]
    public function testThatTheCollectionFromOwningToInversedIsLoaded(): void
    {
        $owning = $this->_em->find(
            OwningManyToManyCompositeIdForeignKeyEntity::class,
            'ghi',
        );

        self::assertCount(1, $owning->associatedEntities);
    }

    #[Depends('testThatEntitiesAreFetchedFromTheDatabase')]
    public function testThatTheCollectionFromInversedToOwningIsLoaded(): void
    {
        $inversed = $this->_em->find(
            InversedManyToManyCompositeIdForeignKeyEntity::class,
            ['id1' => 'def', 'foreignEntity' => 'abc'],
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
            InversedManyToManyCompositeIdForeignKeyEntity::class,
            ['id1' => 'def', 'foreignEntity' => 'abc'],
        );

        foreach ($inversed->associatedEntities as $owning) {
            $inversed->associatedEntities->removeElement($owning);
            $owning->associatedEntities->removeElement($inversed);
        }

        $this->_em->flush();
        $this->_em->clear();

        // test association is removed

        self::assertEquals(0, $conn->fetchOne('SELECT COUNT(*) FROM vct_xref_manytomany_compositeid_foreignkey'));
    }
}
