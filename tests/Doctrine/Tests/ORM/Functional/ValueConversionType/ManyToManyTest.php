<?php

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\Tests\Models;
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

        $this->em->persist($inversed);
        $this->em->persist($owning);

        $this->em->flush();
        $this->em->clear();
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase()
    {
        $conn = $this->em->getConnection();

        self::assertEquals('nop', $conn->fetchColumn('SELECT id1 FROM vct_inversed_manytomany LIMIT 1'));

        self::assertEquals('qrs', $conn->fetchColumn('SELECT id2 FROM vct_owning_manytomany LIMIT 1'));

        self::assertEquals('nop', $conn->fetchColumn('SELECT inversed_id FROM vct_xref_manytomany LIMIT 1'));
        self::assertEquals('qrs', $conn->fetchColumn('SELECT owning_id FROM vct_xref_manytomany LIMIT 1'));
    }

    public function testThatEntitiesAreFetchedFromTheDatabase()
    {
        $inversed = $this->em->find(Entity\InversedManyToManyEntity::class, 'abc');
        $owning   = $this->em->find(Entity\OwningManyToManyEntity::class, 'def');

        self::assertInstanceOf(Entity\InversedManyToManyEntity::class, $inversed);
        self::assertInstanceOf(Entity\OwningManyToManyEntity::class, $owning);
    }

    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase()
    {
        $inversed = $this->em->find(Entity\InversedManyToManyEntity::class, 'abc');
        $owning   = $this->em->find(Entity\OwningManyToManyEntity::class, 'def');

        self::assertEquals('abc', $inversed->id1);
        self::assertEquals('def', $owning->id2);
    }

    public function testThatTheCollectionFromOwningToInversedIsLoaded()
    {
        $owning = $this->em->find(Entity\OwningManyToManyEntity::class, 'def');

        self::assertCount(1, $owning->associatedEntities);
    }

    public function testThatTheCollectionFromInversedToOwningIsLoaded()
    {
        $inversed = $this->em->find(Entity\InversedManyToManyEntity::class, 'abc');

        self::assertCount(1, $inversed->associatedEntities);
    }

    public function testThatTheJoinTableRowsAreRemovedWhenRemovingTheAssociation()
    {
        $conn = $this->em->getConnection();

        // remove association

        $inversed = $this->em->find(Entity\InversedManyToManyEntity::class, 'abc');

        foreach ($inversed->associatedEntities as $owning) {
            $inversed->associatedEntities->removeElement($owning);
            $owning->associatedEntities->removeElement($inversed);
        }

        $this->em->flush();
        $this->em->clear();

        // test association is removed

        self::assertEquals(0, $conn->fetchColumn('SELECT COUNT(*) FROM vct_xref_manytomany'));
    }
}
