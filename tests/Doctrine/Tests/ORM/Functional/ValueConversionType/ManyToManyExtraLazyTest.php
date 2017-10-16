<?php

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\Tests\Models;
use Doctrine\Tests\Models\ValueConversionType as Entity;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * The entities all use a custom type that converst the value as identifier(s).
 * {@see \Doctrine\Tests\DbalTypes\Rot13Type}
 *
 * Test that ManyToMany associations work correctly, focusing on EXTRA_LAZY
 * functionality.
 *
 * @group DDC-3380
 */
class ManyToManyExtraLazyTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('vct_manytomany_extralazy');
        parent::setUp();

        $inversed1 = new Entity\InversedManyToManyExtraLazyEntity();
        $inversed1->id1 = 'abc';

        $inversed2 = new Entity\InversedManyToManyExtraLazyEntity();
        $inversed2->id1 = 'def';

        $owning1 = new Entity\OwningManyToManyExtraLazyEntity();
        $owning1->id2 = 'ghi';

        $owning2 = new Entity\OwningManyToManyExtraLazyEntity();
        $owning2->id2 = 'jkl';

        $inversed1->associatedEntities->add($owning1);
        $owning1->associatedEntities->add($inversed1);
        $inversed1->associatedEntities->add($owning2);
        $owning2->associatedEntities->add($inversed1);

        $inversed2->associatedEntities->add($owning1);
        $owning1->associatedEntities->add($inversed2);
        $inversed2->associatedEntities->add($owning2);
        $owning2->associatedEntities->add($inversed2);

        $this->_em->persist($inversed1);
        $this->_em->persist($inversed2);
        $this->_em->persist($owning1);
        $this->_em->persist($owning2);

        $this->_em->flush();
        $this->_em->clear();
    }

    public static function tearDownAfterClass()
    {
        $conn = static::$_sharedConn;

        $conn->executeUpdate('DROP TABLE vct_xref_manytomany_extralazy');
        $conn->executeUpdate('DROP TABLE vct_owning_manytomany_extralazy');
        $conn->executeUpdate('DROP TABLE vct_inversed_manytomany_extralazy');
    }

    public function testThatTheExtraLazyCollectionFromOwningToInversedIsCounted()
    {
        $owning = $this->_em->find(
            Models\ValueConversionType\OwningManyToManyExtraLazyEntity::class,
            'ghi'
        );

        $this->assertEquals(2, $owning->associatedEntities->count());
    }

    public function testThatTheExtraLazyCollectionFromInversedToOwningIsCounted()
    {
        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedManyToManyExtraLazyEntity::class,
            'abc'
        );

        $this->assertEquals(2, $inversed->associatedEntities->count());
    }

    public function testThatTheExtraLazyCollectionFromOwningToInversedContainsAnEntity()
    {
        $owning = $this->_em->find(
            Models\ValueConversionType\OwningManyToManyExtraLazyEntity::class,
            'ghi'
        );

        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedManyToManyExtraLazyEntity::class,
            'abc'
        );

        $this->assertTrue($owning->associatedEntities->contains($inversed));
    }

    public function testThatTheExtraLazyCollectionFromInversedToOwningContainsAnEntity()
    {
        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedManyToManyExtraLazyEntity::class,
            'abc'
        );

        $owning = $this->_em->find(
            Models\ValueConversionType\OwningManyToManyExtraLazyEntity::class,
            'ghi'
        );

        $this->assertTrue($inversed->associatedEntities->contains($owning));
    }

    public function testThatTheExtraLazyCollectionFromOwningToInversedContainsAnIndexByKey()
    {
        $owning = $this->_em->find(
            Models\ValueConversionType\OwningManyToManyExtraLazyEntity::class,
            'ghi'
        );

        $this->assertTrue($owning->associatedEntities->containsKey('abc'));
    }

    public function testThatTheExtraLazyCollectionFromInversedToOwningContainsAnIndexByKey()
    {
        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedManyToManyExtraLazyEntity::class,
            'abc'
        );

        $this->assertTrue($inversed->associatedEntities->containsKey('ghi'));
    }

    public function testThatASliceOfTheExtraLazyCollectionFromOwningToInversedIsLoaded()
    {
        $owning = $this->_em->find(
            Models\ValueConversionType\OwningManyToManyExtraLazyEntity::class,
            'ghi'
        );

        $this->assertCount(1, $owning->associatedEntities->slice(0, 1));
    }

    public function testThatASliceOfTheExtraLazyCollectionFromInversedToOwningIsLoaded()
    {
        $inversed = $this->_em->find(
            Models\ValueConversionType\InversedManyToManyExtraLazyEntity::class,
            'abc'
        );

        $this->assertCount(1, $inversed->associatedEntities->slice(1, 1));
    }
}
