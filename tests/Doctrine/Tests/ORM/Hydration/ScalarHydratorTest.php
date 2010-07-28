<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\Tests\Mocks\HydratorMockStatement;
use Doctrine\ORM\Query\ResultSetMapping;

require_once __DIR__ . '/../../TestInit.php';

class ScalarHydratorTest extends HydrationTestCase
{
    /**
     * Select u.id, u.name from CmsUser u
     */
    public function testNewHydrationSimpleEntityQuery()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        // Faked result set
        $resultSet = array(
            array(
                'u__id' => '1',
                'u__name' => 'romanb'
                ),
            array(
                'u__id' => '2',
                'u__name' => 'jwage'
                )
            );


        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ScalarHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);

        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
        $this->assertEquals('romanb', $result[0]['u_name']);
        $this->assertEquals(1, $result[0]['u_id']);
        $this->assertEquals('jwage', $result[1]['u_name']);
        $this->assertEquals(2, $result[1]['u_id']);
    }

    /**
     * @group DDC-407
     */
    public function testHydrateScalarResults()
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('foo1', 'foo');
        $rsm->addScalarResult('bar2', 'bar');
        $rsm->addScalarResult('baz3', 'baz');

        $resultSet = array(
            array(
                'foo1' => 'A',
                'bar2' => 'B',
                'baz3' => 'C',
            ),
        );

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ScalarHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);
    }

    /**
     * @group DDC-644
     */
    public function testSkipUnknownColumns()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');
        $rsm->addScalarResult('foo1', 'foo');
        $rsm->addScalarResult('bar2', 'bar');
        $rsm->addScalarResult('baz3', 'baz');

        $resultSet = array(
            array(
                'u__id' => '1',
                'u__name' => 'romanb',
                'foo1' => 'A',
                'bar2' => 'B',
                'baz3' => 'C',
                'foo' => 'bar', // Unknown!
            ),
        );

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ScalarHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);
    }
}