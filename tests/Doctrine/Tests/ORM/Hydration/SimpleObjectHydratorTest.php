<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\Tests\Mocks\HydratorMockStatement;
use Doctrine\ORM\Query\ResultSetMapping;

require_once __DIR__ . '/../../TestInit.php';

class SimpleObjectHydratorTest extends HydrationTestCase
{
    /**
     * @group DDC-1470
     *
     * @expectedException \Doctrine\ORM\Internal\Hydration\HydrationException
     * @expectedExceptionMessage The discriminator column "discr" is missing for "Doctrine\Tests\Models\Company\CompanyPerson" using the DQL alias "p".
     */
    public function testMissingDiscriminatorColumnException()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\Company\CompanyPerson', 'p');
        $rsm->addFieldResult('p', 'p__id', 'id');
        $rsm->addFieldResult('p', 'p__name', 'name');
        $rsm->addMetaResult('p ', 'discr', 'discr');
        $rsm->setDiscriminatorColumn('p', 'discr');
        $resultSet = array(
              array(
                  'u__id'   => '1',
                  'u__name' => 'Fabio B. Silva'
              ),
         );

        $stmt       = new HydratorMockStatement($resultSet);
        $hydrator   = new \Doctrine\ORM\Internal\Hydration\SimpleObjectHydrator($this->_em);
        $hydrator->hydrateAll($stmt, $rsm);
    }

    public function testExtraFieldInResultSetShouldBeIgnore()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsAddress', 'a');
        $rsm->addFieldResult('a', 'a__id', 'id');
        $rsm->addFieldResult('a', 'a__city', 'city');
        $resultSet = array(
            array(
                'a__id'   => '1',
                'a__city' => 'Cracow',
                'doctrine_rownum' => '1'
            ),
        );

        $expectedEntity = new \Doctrine\Tests\Models\CMS\CmsAddress();
        $expectedEntity->id = 1;
        $expectedEntity->city = 'Cracow';

        $stmt       = new HydratorMockStatement($resultSet);
        $hydrator   = new \Doctrine\ORM\Internal\Hydration\SimpleObjectHydrator($this->_em);
        $result = $hydrator->hydrateAll($stmt, $rsm);
        $this->assertEquals($result[0], $expectedEntity);
    }

    /**
     * @group DDC-3076
     *
     * @expectedException \Doctrine\ORM\Internal\Hydration\HydrationException
     * @expectedExceptionMessage The discriminator value "subworker" is invalid. It must be one of "person", "manager", "employee".
     */
    public function testInvalidDiscriminatorValueException()
    {
        $rsm = new ResultSetMapping;

        $rsm->addEntityResult('Doctrine\Tests\Models\Company\CompanyPerson', 'p');

        $rsm->addFieldResult('p', 'p__id', 'id');
        $rsm->addFieldResult('p', 'p__name', 'name');
        $rsm->addMetaResult('p', 'discr', 'discr');
        $rsm->setDiscriminatorColumn('p', 'discr');

        $resultSet = array(
              array(
                  'p__id'   => '1',
                  'p__name' => 'Fabio B. Silva',
                  'discr'   => 'subworker'
              ),
         );

        $stmt       = new HydratorMockStatement($resultSet);
        $hydrator   = new \Doctrine\ORM\Internal\Hydration\SimpleObjectHydrator($this->_em);
        $hydrator->hydrateAll($stmt, $rsm);
    }
}
