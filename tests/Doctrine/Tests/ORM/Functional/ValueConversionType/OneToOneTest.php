<?php

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\Tests\Models;
use Doctrine\Tests\Models\ValueConversionType as Entity;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * The entities all use a custom type that converst the value as identifier(s).
 * {@see \Doctrine\Tests\DbalTypes\Rot13Type}
 *
 * Test that OneToOne associations work correctly.
 *
 * @group DDC-3380
 */
class OneToOneTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('vct_onetoone');

        parent::setUp();

        $inversed = new Entity\InversedOneToOneEntity();
        $inversed->id1 = 'abc';
        $inversed->someProperty = 'some value to be loaded';

        $owning = new Entity\OwningOneToOneEntity();
        $owning->id2 = 'def';

        $inversed->associatedEntity = $owning;
        $owning->associatedEntity = $inversed;

        $this->em->persist($inversed);
        $this->em->persist($owning);

        $this->em->flush();
        $this->em->clear();
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase()
    {
        $conn = $this->em->getConnection();

        self::assertEquals('nop', $conn->fetchColumn('SELECT id1 FROM vct_inversed_onetoone LIMIT 1'));

        self::assertEquals('qrs', $conn->fetchColumn('SELECT id2 FROM vct_owning_onetoone LIMIT 1'));
        self::assertEquals('nop', $conn->fetchColumn('SELECT associated_id FROM vct_owning_onetoone LIMIT 1'));
    }

    public function testThatEntitiesAreFetchedFromTheDatabase()
    {
        $inversed = $this->em->find(Entity\InversedOneToOneEntity::class, 'abc');
        $owning   = $this->em->find(Entity\OwningOneToOneEntity::class, 'def');

        self::assertInstanceOf(Entity\InversedOneToOneEntity::class, $inversed);
        self::assertInstanceOf(Entity\OwningOneToOneEntity::class, $owning);
    }

    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase()
    {
        $inversed = $this->em->find(Entity\InversedOneToOneEntity::class, 'abc');
        $owning   = $this->em->find(Entity\OwningOneToOneEntity::class, 'def');

        self::assertEquals('abc', $inversed->id1);
        self::assertEquals('def', $owning->id2);
    }

    public function testThatTheProxyFromOwningToInversedIsLoaded()
    {
        $owning = $this->em->find(Entity\OwningOneToOneEntity::class, 'def');

        $inversedProxy = $owning->associatedEntity;

        self::assertEquals('some value to be loaded', $inversedProxy->someProperty);
    }

    public function testThatTheEntityFromInversedToOwningIsEagerLoaded()
    {
        $inversed = $this->em->find(Entity\InversedOneToOneEntity::class, 'abc');

        self::assertInstanceOf(Entity\OwningOneToOneEntity::class, $inversed->associatedEntity);
    }
}
