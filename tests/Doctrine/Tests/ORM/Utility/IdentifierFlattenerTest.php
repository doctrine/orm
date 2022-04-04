<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Utility;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Utility\IdentifierFlattener;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\Flight;
use Doctrine\Tests\Models\Enums\Suit;
use Doctrine\Tests\Models\Enums\TypedCardEnumId;
use Doctrine\Tests\Models\VersionedOneToOne\FirstRelatedEntity;
use Doctrine\Tests\Models\VersionedOneToOne\SecondRelatedEntity;
use Doctrine\Tests\OrmFunctionalTestCase;

use const PHP_VERSION_ID;

/**
 * Test the IdentifierFlattener utility class
 *
 * @covers \Doctrine\ORM\Utility\IdentifierFlattener
 */
class IdentifierFlattenerTest extends OrmFunctionalTestCase
{
    /**
     * Identifier flattener
     *
     * @var IdentifierFlattener
     */
    private $identifierFlattener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identifierFlattener = new IdentifierFlattener(
            $this->_em->getUnitOfWork(),
            $this->_em->getMetadataFactory()
        );

        try {
            $schemaArray = [
                $this->_em->getClassMetadata(FirstRelatedEntity::class),
                $this->_em->getClassMetadata(SecondRelatedEntity::class),
                $this->_em->getClassMetadata(Flight::class),
                $this->_em->getClassMetadata(City::class),
            ];

            if (PHP_VERSION_ID >= 80100) {
                $schemaArray[] = $this->_em->getClassMetadata(TypedCardEnumId::class);
            }

            $this->_schemaTool->createSchema($schemaArray);
        } catch (ORMException $e) {
        }
    }

    /**
     * @group utilities
     * @requires PHP 8.1
     */
    public function testFlattenIdentifierWithEnumId(): void
    {
        $typedCardEnumIdEntity       = new TypedCardEnumId();
        $typedCardEnumIdEntity->suit = Suit::Clubs;

        $this->_em->persist($typedCardEnumIdEntity);
        $this->_em->flush();

        $findTypedCardEnumIdEntity = $this->_em->getRepository(TypedCardEnumId::class)->find(Suit::Clubs);

        $class = $this->_em->getClassMetadata(TypedCardEnumId::class);

        $id = $class->getIdentifierValues($findTypedCardEnumIdEntity);

        self::assertCount(1, $id, 'We should have 1 identifier');

        self::assertEquals($id['suit'], Suit::Clubs->value);
    }

    /**
     * @group utilities
     */
    public function testFlattenIdentifierWithOneToOneId(): void
    {
        $secondRelatedEntity       = new SecondRelatedEntity();
        $secondRelatedEntity->name = 'Bob';

        $this->_em->persist($secondRelatedEntity);
        $this->_em->flush();

        $firstRelatedEntity               = new FirstRelatedEntity();
        $firstRelatedEntity->name         = 'Fred';
        $firstRelatedEntity->secondEntity = $secondRelatedEntity;

        $this->_em->persist($firstRelatedEntity);
        $this->_em->flush();

        $firstEntity = $this->_em->getRepository(FirstRelatedEntity::class)
            ->findOneBy(['name' => 'Fred']);

        $class = $this->_em->getClassMetadata(FirstRelatedEntity::class);

        $id = $class->getIdentifierValues($firstEntity);

        self::assertCount(1, $id, 'We should have 1 identifier');

        self::assertArrayHasKey('secondEntity', $id, 'It should be called secondEntity');

        self::assertInstanceOf(
            '\Doctrine\Tests\Models\VersionedOneToOne\SecondRelatedEntity',
            $id['secondEntity'],
            'The entity should be an instance of SecondRelatedEntity'
        );

        $flatIds = $this->identifierFlattener->flattenIdentifier($class, $id);

        self::assertCount(1, $flatIds, 'We should have 1 flattened id');

        self::assertArrayHasKey('secondEntity', $flatIds, 'It should be called secondEntity');

        self::assertEquals($id['secondEntity']->id, $flatIds['secondEntity']);
    }

    /**
     * @group utilities
     */
    public function testFlattenIdentifierWithMutlipleIds(): void
    {
        $leeds  = new City('Leeds');
        $london = new City('London');

        $this->_em->persist($leeds);
        $this->_em->persist($london);
        $this->_em->flush();

        $flight = new Flight($leeds, $london);

        $this->_em->persist($flight);
        $this->_em->flush();

        $class = $this->_em->getClassMetadata(Flight::class);
        $id    = $class->getIdentifierValues($flight);

        self::assertCount(2, $id);

        self::assertArrayHasKey('leavingFrom', $id);
        self::assertArrayHasKey('goingTo', $id);

        self::assertEquals($leeds, $id['leavingFrom']);
        self::assertEquals($london, $id['goingTo']);

        $flatIds = $this->identifierFlattener->flattenIdentifier($class, $id);

        self::assertCount(2, $flatIds);

        self::assertArrayHasKey('leavingFrom', $flatIds);
        self::assertArrayHasKey('goingTo', $flatIds);

        self::assertEquals($id['leavingFrom']->getId(), $flatIds['leavingFrom']);
        self::assertEquals($id['goingTo']->getId(), $flatIds['goingTo']);
    }
}
