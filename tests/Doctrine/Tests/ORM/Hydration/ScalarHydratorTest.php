<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\Tests\Mocks\HydratorMockStatement;

require_once dirname(__FILE__) . '/../../TestInit.php';

/**
 * Description of ScalarHydratorTest
 *
 * @author robo
 */
class ScalarHydratorTest extends HydrationTest
{
    /**
     * Select u.id, u.name from CmsUser u
     */
    public function testNewHydrationSimpleEntityQuery()
    {
        // Faked query components
        $queryComponents = array(
            'u' => array(
                'metadata' => $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser'),
                'parent' => null,
                'relation' => null,
                'map' => null
                )
            );

        // Faked table alias map
        $tableAliasMap = array(
            'u' => 'u'
            );

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

        $result = $hydrator->hydrateAll($stmt, $this->_createParserResult(
                $queryComponents, $tableAliasMap));

        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
        $this->assertEquals('romanb', $result[0]['u_name']);
        $this->assertEquals(1, $result[0]['u_id']);
        $this->assertEquals('jwage', $result[1]['u_name']);
        $this->assertEquals(2, $result[1]['u_id']);
    }
}

