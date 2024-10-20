<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\Tests\Models\ValueConversionType as Entity;
use Doctrine\Tests\Models\ValueConversionType\InversedOneToManyExtraLazyEntity;
use Doctrine\Tests\Models\ValueConversionType\OwningManyToOneExtraLazyEntity;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * The entities all use a custom type that converst the value as identifier(s).
 * {@see \Doctrine\Tests\DbalTypes\Rot13Type}
 *
 * Test that OneToMany associations work correctly, focusing on EXTRA_LAZY
 * functionality.
 */
#[Group('DDC-3380')]
class OneToManyExtraLazyTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('vct_onetomany_extralazy');

        parent::setUp();

        $inversed      = new Entity\InversedOneToManyExtraLazyEntity();
        $inversed->id1 = 'abc';

        $owning1      = new Entity\OwningManyToOneExtraLazyEntity();
        $owning1->id2 = 'def';

        $owning2      = new Entity\OwningManyToOneExtraLazyEntity();
        $owning2->id2 = 'ghi';

        $owning3      = new Entity\OwningManyToOneExtraLazyEntity();
        $owning3->id2 = 'jkl';

        $inversed->associatedEntities->add($owning1);
        $owning1->associatedEntity = $inversed;
        $inversed->associatedEntities->add($owning2);
        $owning2->associatedEntity = $inversed;
        $inversed->associatedEntities->add($owning3);
        $owning3->associatedEntity = $inversed;

        $this->_em->persist($inversed);
        $this->_em->persist($owning1);
        $this->_em->persist($owning2);
        $this->_em->persist($owning3);

        $this->_em->flush();
        $this->_em->clear();
    }

    public static function tearDownAfterClass(): void
    {
        $conn = static::$sharedConn;

        $conn->executeStatement('DROP TABLE vct_owning_manytoone_extralazy');
        $conn->executeStatement('DROP TABLE vct_inversed_onetomany_extralazy');
    }

    public function testThatExtraLazyCollectionIsCounted(): void
    {
        $inversed = $this->_em->find(
            InversedOneToManyExtraLazyEntity::class,
            'abc',
        );

        self::assertEquals(3, $inversed->associatedEntities->count());
    }

    public function testThatExtraLazyCollectionContainsAnEntity(): void
    {
        $inversed = $this->_em->find(
            InversedOneToManyExtraLazyEntity::class,
            'abc',
        );

        $owning = $this->_em->find(
            OwningManyToOneExtraLazyEntity::class,
            'def',
        );

        self::assertTrue($inversed->associatedEntities->contains($owning));
    }

    public function testThatExtraLazyCollectionContainsAnIndexbyKey(): void
    {
        $inversed = $this->_em->find(
            InversedOneToManyExtraLazyEntity::class,
            'abc',
        );

        self::assertTrue($inversed->associatedEntities->containsKey('def'));
    }

    public function testThatASliceOfTheExtraLazyCollectionIsLoaded(): void
    {
        $inversed = $this->_em->find(
            InversedOneToManyExtraLazyEntity::class,
            'abc',
        );

        self::assertCount(2, $inversed->associatedEntities->slice(0, 2));
    }
}
