<?php

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\Tests\Models;
use Doctrine\Tests\Models\ValueConversionType as Entity;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * The entities all use a custom type that converst the value as identifier(s).
 * {@see \Doctrine\Tests\DbalTypes\Rot13Type}
 *
 * Test that OneToMany associations with composite id work correctly.
 *
 * @group DDC-3380
 */
class OneToManyCompositeIdTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('vct_onetomany_compositeid');

        parent::setUp();

        $inversed = new Entity\InversedOneToManyCompositeIdEntity();
        $inversed->id1 = 'abc';
        $inversed->id2 = 'def';
        $inversed->someProperty = 'some value to be loaded';

        $owning = new Entity\OwningManyToOneCompositeIdEntity();
        $owning->id3 = 'ghi';

        $inversed->associatedEntities->add($owning);
        $owning->associatedEntity = $inversed;

        $this->em->persist($inversed);
        $this->em->persist($owning);

        $this->em->flush();
        $this->em->clear();
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase()
    {
        $conn = $this->em->getConnection();

        self::assertEquals('nop', $conn->fetchColumn('SELECT id1 FROM vct_inversed_onetomany_compositeid LIMIT 1'));
        self::assertEquals('qrs', $conn->fetchColumn('SELECT id2 FROM vct_inversed_onetomany_compositeid LIMIT 1'));

        self::assertEquals('tuv', $conn->fetchColumn('SELECT id3 FROM vct_owning_manytoone_compositeid LIMIT 1'));
        self::assertEquals('nop', $conn->fetchColumn('SELECT associated_id1 FROM vct_owning_manytoone_compositeid LIMIT 1'));
        self::assertEquals('qrs', $conn->fetchColumn('SELECT associated_id2 FROM vct_owning_manytoone_compositeid LIMIT 1'));
    }

    public function testThatEntitiesAreFetchedFromTheDatabase()
    {
        $inversed = $this->em->find(
            Entity\InversedOneToManyCompositeIdEntity::class,
            ['id1' => 'abc', 'id2' => 'def']
        );

        $owning = $this->em->find(Entity\OwningManyToOneCompositeIdEntity::class, 'ghi');

        self::assertInstanceOf(Entity\InversedOneToManyCompositeIdEntity::class, $inversed);
        self::assertInstanceOf(Entity\OwningManyToOneCompositeIdEntity::class, $owning);
    }

    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase()
    {
        $inversed = $this->em->find(
            Entity\InversedOneToManyCompositeIdEntity::class,
            ['id1' => 'abc', 'id2' => 'def']
        );

        $owning = $this->em->find(Entity\OwningManyToOneCompositeIdEntity::class, 'ghi');

        self::assertEquals('abc', $inversed->id1);
        self::assertEquals('def', $inversed->id2);
        self::assertEquals('ghi', $owning->id3);
    }

    public function testThatTheProxyFromOwningToInversedIsLoaded()
    {
        $owning = $this->em->find(Entity\OwningManyToOneCompositeIdEntity::class, 'ghi');

        $inversedProxy = $owning->associatedEntity;

        self::assertEquals('some value to be loaded', $inversedProxy->someProperty);
    }

    public function testThatTheCollectionFromInversedToOwningIsLoaded()
    {
        $inversed = $this->em->find(
            Entity\InversedOneToManyCompositeIdEntity::class,
            ['id1' => 'abc', 'id2' => 'def']
        );

        self::assertCount(1, $inversed->associatedEntities);
    }
}
