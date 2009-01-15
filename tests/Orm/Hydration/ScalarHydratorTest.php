<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'HydrationTest.php';

/**
 * Description of ScalarHydratorTest
 *
 * @author robo
 */
class Orm_Hydration_ScalarHydratorTest extends Orm_Hydration_HydrationTest
{
    /**
     * Select u.id, u.name from CmsUser u
     */
    public function testNewHydrationSimpleEntityQuery()
    {
        // Faked query components
        $queryComponents = array(
            'u' => array(
                'metadata' => $this->_em->getClassMetadata('CmsUser'),
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


        $stmt = new Doctrine_HydratorMockStatement($resultSet);
        $hydrator = new Doctrine_ORM_Internal_Hydration_ScalarHydrator($this->_em);

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

