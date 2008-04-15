<?php
require_once 'lib/DoctrineTestInit.php';
require_once 'lib/mocks/Doctrine_HydratorMockStatement.php';
 
class Orm_Hydration_BasicHydrationTest extends Doctrine_OrmTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }
    
    /**
     * Fakes the DQL query: select u.id, u.name from CmsUser u
     *
     */
    public function testBasic()
    {
        // Faked query components
        $queryComponents = array(
            'u' => array(
                'table' => $this->sharedFixture['connection']->getClassMetadata('CmsUser'),
                'mapper' => $this->sharedFixture['connection']->getMapper('CmsUser'),
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
            //row1
            array(
                'u__id' => '1',
                'u__name' => 'romanb'
                ),
            //row2
            array(
                'u__id' => '2',
                'u__name' => 'jwage'
                )
            );
        
            
        $stmt = new Doctrine_HydratorMockStatement($resultSet);
        $hydrator = new Doctrine_Hydrator();
        $hydrator->setQueryComponents($queryComponents);
        
        $arrayResult = $hydrator->hydrateResultSet($stmt, $tableAliasMap, Doctrine::HYDRATE_ARRAY);        
        
        $this->assertTrue(is_array($arrayResult));
        $this->assertEquals(2, count($arrayResult));
        $this->assertEquals(1, $arrayResult[0]['id']);
        $this->assertEquals('romanb', $arrayResult[0]['name']);
        $this->assertEquals(2, $arrayResult[1]['id']);
        $this->assertEquals('jwage', $arrayResult[1]['name']);
        
        $stmt->setResultSet($resultSet);
        $objectResult = $hydrator->hydrateResultSet($stmt, $tableAliasMap, Doctrine::HYDRATE_RECORD);    
        
        $this->assertTrue($objectResult instanceof Doctrine_Collection);
        $this->assertEquals(2, count($objectResult));
        $this->assertTrue($objectResult[0] instanceof Doctrine_Record);
        $this->assertEquals(1, $objectResult[0]->id);
        $this->assertEquals('romanb', $objectResult[0]->name);
        $this->assertTrue($objectResult[1] instanceof Doctrine_Record);
        $this->assertEquals(2, $objectResult[1]->id);
        $this->assertEquals('jwage', $objectResult[1]->name);
        
    }
}