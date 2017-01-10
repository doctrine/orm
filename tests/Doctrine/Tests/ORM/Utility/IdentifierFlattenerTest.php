<?php

namespace Doctrine\Tests\ORM\Utility;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\Models\VersionedOneToOne\FirstRelatedEntity;
use Doctrine\Tests\Models\VersionedOneToOne\SecondRelatedEntity;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\Flight;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Utility\IdentifierFlattener;

/**
 * Test the IdentifierFlattener utility class
 *
 * @author Rob Caiger <rob@clocal.co.uk>
 * @covers \Doctrine\ORM\Utility\IdentifierFlattener
 */
class IdentifierFlattenerTest extends OrmFunctionalTestCase
{
    /**
     * Identifier flattener
     *
     * @var \Doctrine\ORM\Utility\IdentifierFlattener
     */
    private $identifierFlattener;

    protected function setUp()
    {
        parent::setUp();

        $this->identifierFlattener = new IdentifierFlattener(
            $this->em->getUnitOfWork(),
            $this->em->getMetadataFactory()
        );

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(FirstRelatedEntity::class),
                    $this->em->getClassMetadata(SecondRelatedEntity::class),
                    $this->em->getClassMetadata(Flight::class),
                    $this->em->getClassMetadata(City::class)
                ]
            );
        } catch (ORMException $e) {
        }
    }

    /**
     * @group utilities
     */
    public function testFlattenIdentifierWithOneToOneId()
    {
        $secondRelatedEntity = new SecondRelatedEntity();
        $secondRelatedEntity->name = 'Bob';

        $this->em->persist($secondRelatedEntity);
        $this->em->flush();

        $firstRelatedEntity = new FirstRelatedEntity();
        $firstRelatedEntity->name = 'Fred';
        $firstRelatedEntity->secondEntity = $secondRelatedEntity;

        $this->em->persist($firstRelatedEntity);
        $this->em->flush();

        $firstEntity = $this->em->getRepository(FirstRelatedEntity::class)
            ->findOneBy(['name' => 'Fred']);

        $class = $this->em->getClassMetadata(FirstRelatedEntity::class);

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
    public function testFlattenIdentifierWithMutlipleIds()
    {
        $leeds = new City('Leeds');
        $london = new City('London');

        $this->em->persist($leeds);
        $this->em->persist($london);
        $this->em->flush();

        $flight = new Flight($leeds, $london);

        $this->em->persist($flight);
        $this->em->flush();

        $class = $this->em->getClassMetadata(Flight::class);
        $id = $class->getIdentifierValues($flight);

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
