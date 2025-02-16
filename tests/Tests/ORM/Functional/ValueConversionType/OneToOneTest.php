<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\ValueConversionType;

use Doctrine\Tests\Models\ValueConversionType as Entity;
use Doctrine\Tests\Models\ValueConversionType\InversedOneToOneEntity;
use Doctrine\Tests\Models\ValueConversionType\OwningOneToOneEntity;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;

/**
 * The entities all use a custom type that converst the value as identifier(s).
 * {@see \Doctrine\Tests\DbalTypes\Rot13Type}
 *
 * Test that OneToOne associations work correctly.
 */
#[Group('DDC-3380')]
class OneToOneTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('vct_onetoone');

        parent::setUp();

        $inversed               = new Entity\InversedOneToOneEntity();
        $inversed->id1          = 'abc';
        $inversed->someProperty = 'some value to be loaded';

        $owning      = new Entity\OwningOneToOneEntity();
        $owning->id2 = 'def';

        $inversed->associatedEntity = $owning;
        $owning->associatedEntity   = $inversed;

        $this->_em->persist($inversed);
        $this->_em->persist($owning);

        $this->_em->flush();
        $this->_em->clear();
    }

    public static function tearDownAfterClass(): void
    {
        $conn = static::$sharedConn;

        $conn->executeStatement('DROP TABLE vct_owning_onetoone');
        $conn->executeStatement('DROP TABLE vct_inversed_onetoone');
    }

    public function testThatTheValueOfIdentifiersAreConvertedInTheDatabase(): void
    {
        $conn = $this->_em->getConnection();

        self::assertEquals('nop', $conn->fetchOne('SELECT id1 FROM vct_inversed_onetoone LIMIT 1'));

        self::assertEquals('qrs', $conn->fetchOne('SELECT id2 FROM vct_owning_onetoone LIMIT 1'));
        self::assertEquals('nop', $conn->fetchOne('SELECT associated_id FROM vct_owning_onetoone LIMIT 1'));
    }

    #[Depends('testThatTheValueOfIdentifiersAreConvertedInTheDatabase')]
    public function testThatEntitiesAreFetchedFromTheDatabase(): void
    {
        $inversed = $this->_em->find(
            InversedOneToOneEntity::class,
            'abc',
        );

        $owning = $this->_em->find(
            OwningOneToOneEntity::class,
            'def',
        );

        self::assertInstanceOf(InversedOneToOneEntity::class, $inversed);
        self::assertInstanceOf(OwningOneToOneEntity::class, $owning);
    }

    #[Depends('testThatEntitiesAreFetchedFromTheDatabase')]
    public function testThatTheValueOfIdentifiersAreConvertedBackAfterBeingFetchedFromTheDatabase(): void
    {
        $inversed = $this->_em->find(
            InversedOneToOneEntity::class,
            'abc',
        );

        $owning = $this->_em->find(
            OwningOneToOneEntity::class,
            'def',
        );

        self::assertEquals('abc', $inversed->id1);
        self::assertEquals('def', $owning->id2);
    }

    #[Depends('testThatEntitiesAreFetchedFromTheDatabase')]
    public function testThatTheProxyFromOwningToInversedIsLoaded(): void
    {
        $owning = $this->_em->find(
            OwningOneToOneEntity::class,
            'def',
        );

        $inversedProxy = $owning->associatedEntity;

        self::assertEquals('some value to be loaded', $inversedProxy->someProperty);
    }

    #[Depends('testThatEntitiesAreFetchedFromTheDatabase')]
    public function testThatTheEntityFromInversedToOwningIsEagerLoaded(): void
    {
        $inversed = $this->_em->find(
            InversedOneToOneEntity::class,
            'abc',
        );

        self::assertInstanceOf(OwningOneToOneEntity::class, $inversed->associatedEntity);
    }
}
