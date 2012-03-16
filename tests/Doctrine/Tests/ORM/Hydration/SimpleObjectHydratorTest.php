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
}
