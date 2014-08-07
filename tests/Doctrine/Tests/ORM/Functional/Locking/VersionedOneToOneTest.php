<?php

namespace Doctrine\Tests\ORM\Functional\Locking;

use Doctrine\Tests\Models\VersionedOneToOne\FirstRelatedEntity;
use Doctrine\Tests\Models\VersionedOneToOne\SecondRelatedEntity;

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
        } catch(\Exception $e) {
        }
    }

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
    }
}
