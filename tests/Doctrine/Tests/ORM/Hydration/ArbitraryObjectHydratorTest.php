<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\Internal\Hydration\ArbitraryObjectHydrator;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Mocks\HydratorMockStatement;

require_once __DIR__ . '/../../TestInit.php';

/**
 * This unit test asserts if hydration of arbitrary objects (non-entities) is
 * functioning correctly.
 */
class ArbitraryObjectHydratorTest extends HydrationTestCase
{
    /**
     * This test mimics the execution of the following SQL statement:
     * 
     * SELECT c.*, a.* FROM ddc1819_customer c LEFT JOIN ddc1819_address a
     *     ON c.address_id = a.id
     *
     * The result set is mapped back to CustomerAddressView objects.
     */
    public function testSimpleObjectQueryFromTwoTables()
    {
        $rsm = new ResultSetMapping();

        $rsm->addEntityResult('Doctrine\Tests\Models\DDC1819\DataTransferObject\CustomerAddressView', 'v');
        $rsm->addFieldResult('v', 'c__id', 'customerId');
        $rsm->addFieldResult('v', 'c__name', 'customerName');
        $rsm->addFieldResult('v', 'a__id', 'customerId');
        $rsm->addFieldResult('v', 'a__name', 'customerName');
        $rsm->addFieldResult('v', 'a__id', 'customerId');
        $rsm->addFieldResult('v', 'a__name', 'customerName');

        // TODO: use a data provider for the result set
        $resultSet = array(
            array(
                'c__id' => '1',
                'c__name' => 'John King',
                'a__id' => '8',
                'a__street' => 'Burnside Court',
                'a__number' => '516',
                'a__city' => 'Phoenix',
                'a__code' => '85003'
            ),
            array(
                'c__id' => '2',
                'c__name' => 'Gail Napier',
                'a__id' => '9',
                'a__street' => 'Reppert Coal Road',
                'a__number' => '351',
                'a__city' => 'Warren',
                'a__code' => '48093'
            )
        );

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new ArbitraryObjectHydrator($this->_em);
        $result = $hydrator->hydrateAll($stmt, $rsm, array());

        $this->assertEquals(2, count($result));

        $this->assertInstanceOf('Doctrine\Tests\Models\DDC1819\DataTransferObject\CustomerAddressView', $result[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\DDC1819\DataTransferObject\CustomerAddressView', $result[1]);

        // TODO: assert using the data in the resultSet variable
        $this->assertEquals(1, $result[0]->getCustomerId());
        $this->assertEquals('John King', $result[0]->getCustomerName());
        $this->assertEquals(8, $result[0]->getAddressId());
        $this->assertEquals('Burnside Court', $result[0]->getAddressStreet());
        $this->assertEquals('516', $result[0]->getAddressNumber());
        $this->assertEquals('Phoenix', $result[0]->getAddressCity());
        $this->assertEquals('85003', $result[0]->getAddressCode());

        $this->assertEquals(2, $result[1]->getCustomerId());
        $this->assertEquals('Gail Napier', $result[1]->getCustomerName());
        $this->assertEquals(9, $result[1]->getAddressId());
        $this->assertEquals('Reppert Coal Road', $result[1]->getAddressStreet());
        $this->assertEquals('351', $result[1]->getAddressNumber());
        $this->assertEquals('Warren', $result[1]->getAddressCity());
        $this->assertEquals('48093', $result[1]->getAddressCode());
    }
}
