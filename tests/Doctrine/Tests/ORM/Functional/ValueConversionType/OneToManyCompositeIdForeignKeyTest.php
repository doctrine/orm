<?php

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\Tests\Models;
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
        $this->useModelSet('vct_onetomany_compositeid_foreignkey');

        parent::setUp();

        $auxiliary = new Entity\AuxiliaryEntity();
        $auxiliary->id4 = 'abc';

        $inversed = new Entity\InversedOneToManyCompositeIdForeignKeyEntity();
        $inversed->id1 = 'def';
        $inversed->foreignEntity = $auxiliary;
        $inversed->someProperty = 'some value to be loaded';

        $owning = new Entity\OwningManyToOneCompositeIdForeignKeyEntity();
        $owning->id2 = 'ghi';

        $inversed->associatedEntities->add($owning);
        $owning->associatedEntity = $inversed;

        $this->em->persist($auxiliary);
        $this->em->persist($inversed);
        $this->em->persist($owning);

        $this->em->flush();
        $this->em->clear();
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase()
    {
        $conn = $this->em->getConnection();

        self::assertEquals('nop', $conn->fetchColumn('SELECT id4 FROM vct_auxiliary LIMIT 1'));

        self::assertEquals('qrs', $conn->fetchColumn('SELECT id1 FROM vct_inversed_onetomany_compositeid_foreignkey LIMIT 1'));
        self::assertEquals('nop', $conn->fetchColumn('SELECT foreign_id FROM vct_inversed_onetomany_compositeid_foreignkey LIMIT 1'));

        self::assertEquals('tuv', $conn->fetchColumn('SELECT id2 FROM vct_owning_manytoone_compositeid_foreignkey LIMIT 1'));
        self::assertEquals('qrs', $conn->fetchColumn('SELECT associated_id FROM vct_owning_manytoone_compositeid_foreignkey LIMIT 1'));
        self::assertEquals('nop', $conn->fetchColumn('SELECT associated_foreign_id FROM vct_owning_manytoone_compositeid_foreignkey LIMIT 1'));
    }

    public function testThatEntitiesAreFetchedFromTheDatabase()
    {
        $auxiliary = $this->em->find(Entity\AuxiliaryEntity::class, 'abc');

        $inversed = $this->em->find(
            Entity\InversedOneToManyCompositeIdForeignKeyEntity::class,
            ['id1' => 'def', 'foreignEntity' => 'abc']
        );

        $owning = $this->em->find(Entity\OwningManyToOneCompositeIdForeignKeyEntity::class, 'ghi');

        self::assertInstanceOf(Entity\AuxiliaryEntity::class, $auxiliary);
        self::assertInstanceOf(Entity\InversedOneToManyCompositeIdForeignKeyEntity::class, $inversed);
        self::assertInstanceOf(Entity\OwningManyToOneCompositeIdForeignKeyEntity::class, $owning);
    }

    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase()
    {
        $auxiliary = $this->em->find(Entity\AuxiliaryEntity::class, 'abc');

        $inversed = $this->em->find(
            Entity\InversedOneToManyCompositeIdForeignKeyEntity::class,
            ['id1' => 'def', 'foreignEntity' => 'abc']
        );

        $owning = $this->em->find(Entity\OwningManyToOneCompositeIdForeignKeyEntity::class, 'ghi');

        self::assertEquals('abc', $auxiliary->id4);
        self::assertEquals('def', $inversed->id1);
        self::assertEquals('abc', $inversed->foreignEntity->id4);
        self::assertEquals('ghi', $owning->id2);
    }

    public function testThatInversedEntityIsFetchedFromTheDatabaseUsingAuxiliaryEntityAsId()
    {
        $auxiliary = $this->em->find(Entity\AuxiliaryEntity::class, 'abc');

        $inversed = $this->em->find(
            Entity\InversedOneToManyCompositeIdForeignKeyEntity::class,
            ['id1' => 'def', 'foreignEntity' => $auxiliary]
        );

        self::assertInstanceOf(Entity\InversedOneToManyCompositeIdForeignKeyEntity::class, $inversed);
    }

    public function testThatTheProxyFromOwningToInversedIsLoaded()
    {
        $owning = $this->em->find(Entity\OwningManyToOneCompositeIdForeignKeyEntity::class, 'ghi');

        $inversedProxy = $owning->associatedEntity;

        self::assertSame('def', $inversedProxy->id1, 'Proxy identifier is converted');

        self::assertEquals('some value to be loaded', $inversedProxy->someProperty);
    }

    public function testThatTheCollectionFromInversedToOwningIsLoaded()
    {
        $inversed = $this->em->find(
            Entity\InversedOneToManyCompositeIdForeignKeyEntity::class,
            ['id1' => 'def', 'foreignEntity' => 'abc']
        );

        self::assertCount(1, $inversed->associatedEntities);
    }
}
