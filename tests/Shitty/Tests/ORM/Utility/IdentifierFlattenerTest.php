<?php

namespace Shitty\Tests\ORM\Utility;

use Shitty\Tests\OrmFunctionalTestCase;
use Shitty\Tests\Models\VersionedOneToOne\FirstRelatedEntity;
use Shitty\Tests\Models\VersionedOneToOne\SecondRelatedEntity;
use Shitty\Tests\Models\Cache\City;
use Shitty\Tests\Models\Cache\Flight;
use Shitty\ORM\ORMException;
use Shitty\ORM\Utility\IdentifierFlattener;

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
     * @var \Shitty\ORM\Utility\IdentifierFlattener
     */
    private $identifierFlattener;

    protected function setUp()
    {
        parent::setUp();

        $this->identifierFlattener = new IdentifierFlattener(
            $this->_em->getUnitOfWork(),
            $this->_em->getMetadataFactory()
        );

        try {
            $this->_schemaTool->createSchema(
                array(
                    $this->_em->getClassMetadata('Doctrine\Tests\Models\VersionedOneToOne\FirstRelatedEntity'),
                    $this->_em->getClassMetadata('Doctrine\Tests\Models\VersionedOneToOne\SecondRelatedEntity'),
                    $this->_em->getClassMetadata('Doctrine\Tests\Models\Cache\Flight'),
                    $this->_em->getClassMetadata('Doctrine\Tests\Models\Cache\City')
                )
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

        $this->_em->persist($secondRelatedEntity);
        $this->_em->flush();

        $firstRelatedEntity = new FirstRelatedEntity();
        $firstRelatedEntity->name = 'Fred';
        $firstRelatedEntity->secondEntity = $secondRelatedEntity;

        $this->_em->persist($firstRelatedEntity);
        $this->_em->flush();

        $firstEntity = $this->_em->getRepository('Doctrine\Tests\Models\VersionedOneToOne\FirstRelatedEntity')
            ->findOneBy(array('name' => 'Fred'));

        $class = $this->_em->getClassMetadata('Doctrine\Tests\Models\VersionedOneToOne\FirstRelatedEntity');

        $id = $class->getIdentifierValues($firstEntity);

        $this->assertCount(1, $id, 'We should have 1 identifier');

        $this->assertArrayHasKey('secondEntity', $id, 'It should be called secondEntity');

        $this->assertInstanceOf(
            '\Doctrine\Tests\Models\VersionedOneToOne\SecondRelatedEntity',
            $id['secondEntity'],
            'The entity should be an instance of SecondRelatedEntity'
        );

        $flatIds = $this->identifierFlattener->flattenIdentifier($class, $id);

        $this->assertCount(1, $flatIds, 'We should have 1 flattened id');

        $this->assertArrayHasKey('secondEntity', $flatIds, 'It should be called secondEntity');

        $this->assertEquals($id['secondEntity']->id, $flatIds['secondEntity']);
    }

    /**
     * @group utilities
     */
    public function testFlattenIdentifierWithMutlipleIds()
    {
        $leeds = new City('Leeds');
        $london = new City('London');

        $this->_em->persist($leeds);
        $this->_em->persist($london);
        $this->_em->flush();

        $flight = new Flight($leeds, $london);

        $this->_em->persist($flight);
        $this->_em->flush();

        $class = $this->_em->getClassMetadata('Doctrine\Tests\Models\Cache\Flight');
        $id = $class->getIdentifierValues($flight);

        $this->assertCount(2, $id);

        $this->assertArrayHasKey('leavingFrom', $id);
        $this->assertArrayHasKey('goingTo', $id);

        $this->assertEquals($leeds, $id['leavingFrom']);
        $this->assertEquals($london, $id['goingTo']);

        $flatIds = $this->identifierFlattener->flattenIdentifier($class, $id);

        $this->assertCount(2, $flatIds);

        $this->assertArrayHasKey('leavingFrom', $flatIds);
        $this->assertArrayHasKey('goingTo', $flatIds);

        $this->assertEquals($id['leavingFrom']->getId(), $flatIds['leavingFrom']);
        $this->assertEquals($id['goingTo']->getId(), $flatIds['goingTo']);
    }
}
