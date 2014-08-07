<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\VersionedOneToOne\FirstRelatedEntity;
use Doctrine\Tests\Models\VersionedOneToOne\SecondRelatedEntity;
use Doctrine\ORM\ORMException;

/**
 * Tests that an entity with a OneToOne relationship defined as the id, with a version field can be created.
 *
 * @author Rob Caiger <rob@clocal.co.uk>
 *
 * @group VersionedOneToOne
 */
class VersionedOneToOneTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                array(
                    $this->_em->getClassMetadata('Doctrine\Tests\Models\VersionedOneToOne\FirstRelatedEntity'),
                    $this->_em->getClassMetadata('Doctrine\Tests\Models\VersionedOneToOne\SecondRelatedEntity')
                )
            );
        } catch (ORMException $e) {
        }
    }

    /**
     * This test case tests that a versionable entity, that has a oneToOne relationship as it's id can be created
     *  without this bug fix (DDC-3318), you could not do this
     */
    public function testSetVersionOnCreate()
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

        $secondEntity = $this->_em->getRepository('Doctrine\Tests\Models\VersionedOneToOne\SecondRelatedEntity')
            ->findOneBy(array('name' => 'Bob'));

        $this->assertSame($firstRelatedEntity, $firstEntity);
        $this->assertSame($secondRelatedEntity, $secondEntity);
        $this->assertSame($firstEntity->secondEntity, $secondEntity);
    }
}
