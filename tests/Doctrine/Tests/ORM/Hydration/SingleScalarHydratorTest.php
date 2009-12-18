<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\Tests\Mocks\HydratorMockStatement;
use Doctrine\ORM\Query\ResultSetMapping;

require_once __DIR__ . '/../../TestInit.php';

class SingleScalarHydratorTest extends HydrationTestCase
{
    /** Result set provider for the HYDRATE_SINGLE_SCALAR tests */
    public static function singleScalarResultSetProvider() {
        return array(
          // valid
          array('name' => 'result1',
                'resultSet' => array(
                  array(
                      'u__name' => 'romanb'
                  )
               )),
          // valid
          array('name' => 'result2',
                'resultSet' => array(
                  array(
                      'u__id' => '1'
                  )
             )),
           // invalid
           array('name' => 'result3',
                'resultSet' => array(
                  array(
                      'u__id' => '1',
                      'u__name' => 'romanb'
                  )
             )),
           // invalid
           array('name' => 'result4',
                'resultSet' => array(
                  array(
                      'u__id' => '1'
                  ),
                  array(
                      'u__id' => '2'
                  )
             )),
        );
    }

    /**
     * select u.name from CmsUser u where u.id = 1
     *
     * @dataProvider singleScalarResultSetProvider
     */
    public function testHydrateSingleScalar($name, $resultSet)
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\SingleScalarHydrator($this->_em);

        if ($name == 'result1') {
            $result = $hydrator->hydrateAll($stmt, $rsm);
            $this->assertEquals('romanb', $result);
        } else if ($name == 'result2') {
            $result = $hydrator->hydrateAll($stmt, $rsm);
            $this->assertEquals(1, $result);
        } else if ($name == 'result3' || $name == 'result4') {
            try {
                $result = $hydrator->hydrateAll($stmt, $rsm);
                $this->fail();
            } catch (\Doctrine\ORM\NonUniqueResultException $e) {}
        }
    }
}