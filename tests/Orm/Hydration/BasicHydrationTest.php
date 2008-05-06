<?php
require_once 'lib/DoctrineTestInit.php';
require_once 'lib/mocks/Doctrine_HydratorMockStatement.php';
 
class Orm_Hydration_BasicHydrationTest extends Doctrine_OrmTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }
    
    /** The data of the hydration mode dataProvider */
    protected static $hydrationModeProviderData = array(
      array('hydrationMode' => Doctrine::HYDRATE_RECORD),
      array('hydrationMode' => Doctrine::HYDRATE_ARRAY)
    );
    /** Getter for the hydration mode dataProvider */
    public static function hydrationModeProvider()
    {
        return self::$hydrationModeProviderData;
    }
    
    /**
     * Select u.id, u.name from CmsUser u
     *
     */
    public function testBasicHydration()
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
    
    /**
     * select u.id, u.status, p.phonenumber, upper(u.name) nameUpper from User u
     * join u.phonenumbers p
     * =
     * select u.id, u.status, p.phonenumber, upper(u.name) as u__0 from USERS u
     * INNER JOIN PHONENUMBERS p ON u.id = p.user_id
     * 
     * @dataProvider hydrationModeProvider
     */
    public function testNewHydrationMixedQueryFetchJoin($hydrationMode)
    {
        // Faked query components
        $queryComponents = array(
            'u' => array(
                'table' => $this->sharedFixture['connection']->getClassMetadata('CmsUser'),
                'mapper' => $this->sharedFixture['connection']->getMapper('CmsUser'),
                'parent' => null,
                'relation' => null,
                'map' => null,
                'agg' => array('0' => 'nameUpper')
                ),
            'p' => array(
                'table' => $this->sharedFixture['connection']->getClassMetadata('CmsPhonenumber'),
                'mapper' => $this->sharedFixture['connection']->getMapper('CmsPhonenumber'),
                'parent' => 'u',
                'relation' => $this->sharedFixture['connection']->getClassMetadata('CmsUser')->getRelation('phonenumbers'),
                'map' => null
                )
            );
        
        // Faked table alias map
        $tableAliasMap = array(
            'u' => 'u',
            'p' => 'p'
            );
        
        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__0' => 'ROMANB',
                'p__phonenumber' => '42',
                ),
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__0' => 'ROMANB',
                'p__phonenumber' => '43',
                ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'u__0' => 'JWAGE',
                'p__phonenumber' => '91'
                )
            );
            
        $stmt = new Doctrine_HydratorMockStatement($resultSet);
        $hydrator = new Doctrine_HydratorNew();
        $hydrator->setQueryComponents($queryComponents);
        
        $hydrator->setResultMixed(true);
        
        $result = $hydrator->hydrateResultSet($stmt, $tableAliasMap, $hydrationMode);
        //var_dump($result);
        
        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));
        
        $this->assertEquals(3, count($result[0][0]));
        // first user => 2 phonenumbers
        $this->assertEquals(2, count($result[0][0]['phonenumbers']));
        $this->assertEquals('ROMANB', $result[0]['nameUpper']);
        // second user => 1 phonenumber
        $this->assertEquals(1, count($result[1][0]['phonenumbers']));
        $this->assertEquals('JWAGE', $result[1]['nameUpper']);
        
        $this->assertEquals(42, $result[0][0]['phonenumbers'][0]['phonenumber']);
        $this->assertEquals(43, $result[0][0]['phonenumbers'][1]['phonenumber']);
        $this->assertEquals(91, $result[1][0]['phonenumbers'][0]['phonenumber']);
        
        if ($hydrationMode == Doctrine::HYDRATE_RECORD) {
            $this->assertTrue($result[0][0] instanceof Doctrine_Record);
            $this->assertTrue($result[0][0]['phonenumbers'] instanceof Doctrine_Collection);
            $this->assertTrue($result[0][0]['phonenumbers'][0] instanceof Doctrine_Record);
            $this->assertTrue($result[0][0]['phonenumbers'][1] instanceof Doctrine_Record);
            $this->assertTrue($result[1][0] instanceof Doctrine_Record);
            $this->assertTrue($result[1][0]['phonenumbers'] instanceof Doctrine_Collection);
        } 
    }
    
    /**
     * select u.id, u.status, count(p.phonenumber) numPhones from User u
     * join u.phonenumbers p group by u.status, u.id
     * =
     * select u.id, u.status, count(p.phonenumber) as p__0 from USERS u
     * INNER JOIN PHONENUMBERS p ON u.id = p.user_id group by u.id, u.status
     * 
     * @dataProvider hydrationModeProvider
     */
    public function testNewHydrationBasicsMixedQueryNormalJoin($hydrationMode)
    {
        // Faked query components
        $queryComponents = array(
            'u' => array(
                'table' => $this->sharedFixture['connection']->getClassMetadata('CmsUser'),
                'mapper' => $this->sharedFixture['connection']->getMapper('CmsUser'),
                'parent' => null,
                'relation' => null,
                'map' => null
                ),
            'p' => array(
                'table' => $this->sharedFixture['connection']->getClassMetadata('CmsPhonenumber'),
                'mapper' => $this->sharedFixture['connection']->getMapper('CmsPhonenumber'),
                'parent' => 'u',
                'relation' => $this->sharedFixture['connection']->getClassMetadata('CmsUser')->getRelation('phonenumbers'),
                'map' => null,
                'agg' => array('0' => 'numPhones')
                )
            );
        
        // Faked table alias map
        $tableAliasMap = array(
            'u' => 'u',
            'p' => 'p'
            );
        
        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'p__0' => '2',
                ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'p__0' => '1',
                )
            );
        
            
        $stmt = new Doctrine_HydratorMockStatement($resultSet);
        $hydrator = new Doctrine_HydratorNew();
        $hydrator->setQueryComponents($queryComponents);
        
        $hydrator->setResultMixed(true);
        
        $result = $hydrator->hydrateResultSet($stmt, $tableAliasMap, $hydrationMode);
        //var_dump($result);
        
        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));
        
        // first user => 2 phonenumbers
        $this->assertEquals(2, $result[0]['numPhones']);
        // second user => 1 phonenumber
        $this->assertEquals(1, $result[1]['numPhones']);
        
        if ($hydrationMode == Doctrine::HYDRATE_RECORD) {
            $this->assertTrue($result[0][0] instanceof Doctrine_Record);
            $this->assertTrue($result[1][0] instanceof Doctrine_Record);
        }
    }
    
    /** 
     * select u.id, u.status, upper(u.name) nameUpper from User u index by u.id
     * join u.phonenumbers p indexby p.phonenumber
     * =
     * select u.id, u.status, upper(u.name) as p__0 from USERS u
     * INNER JOIN PHONENUMBERS p ON u.id = p.user_id
     * 
     * @dataProvider hydrationModeProvider
     */
    public function testNewHydrationMixedQueryFetchJoinCustomIndex($hydrationMode)
    {
        // Faked query components
        $queryComponents = array(
            'u' => array(
                'table' => $this->sharedFixture['connection']->getClassMetadata('CmsUser'),
                'mapper' => $this->sharedFixture['connection']->getMapper('CmsUser'),
                'parent' => null,
                'relation' => null,
                'agg' => array('0' => 'nameUpper'),
                'map' => 'id'
                ),
            'p' => array(
                'table' => $this->sharedFixture['connection']->getClassMetadata('CmsPhonenumber'),
                'mapper' => $this->sharedFixture['connection']->getMapper('CmsPhonenumber'),
                'parent' => 'u',
                'relation' => $this->sharedFixture['connection']->getClassMetadata('CmsUser')->getRelation('phonenumbers'),
                'map' => 'phonenumber'
                )
            );
        
        // Faked table alias map
        $tableAliasMap = array(
            'u' => 'u',
            'p' => 'p'
            );
        
        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__0' => 'ROMANB',
                'p__phonenumber' => '42',
                ),
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__0' => 'ROMANB',
                'p__phonenumber' => '43',
                ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'u__0' => 'JWAGE',
                'p__phonenumber' => '91'
                )
            );
        
            
        $stmt = new Doctrine_HydratorMockStatement($resultSet);
        $hydrator = new Doctrine_HydratorNew();
        $hydrator->setQueryComponents($queryComponents);
        
        // give the hydrator an artificial hint
        $hydrator->setResultMixed(true);
        
        $result = $hydrator->hydrateResultSet($stmt, $tableAliasMap, $hydrationMode);
        if ($hydrationMode == Doctrine::HYDRATE_ARRAY) {
            //var_dump($result);
        }
        
        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));
        
        
        // first user => 2 phonenumbers. notice the custom indexing by user id
        $this->assertEquals(2, count($result[0]['1']['phonenumbers']));
        // second user => 1 phonenumber. notice the custom indexing by user id
        $this->assertEquals(1, count($result[1]['2']['phonenumbers']));
        
        // test the custom indexing of the phonenumbers
        $this->assertTrue(isset($result[0]['1']['phonenumbers']['42']));
        $this->assertTrue(isset($result[0]['1']['phonenumbers']['43']));
        $this->assertTrue(isset($result[1]['2']['phonenumbers']['91']));
        
        // test the scalar values
        $this->assertEquals('ROMANB', $result[0]['nameUpper']);
        $this->assertEquals('JWAGE', $result[1]['nameUpper']);
        
        if ($hydrationMode == Doctrine::HYDRATE_RECORD) {
            $this->assertTrue($result[0]['1'] instanceof Doctrine_Record);
            $this->assertTrue($result[1]['2'] instanceof Doctrine_Record);
            $this->assertTrue($result[0]['1']['phonenumbers'] instanceof Doctrine_Collection);
            $this->assertEquals(2, count($result[0]['1']['phonenumbers']));
        }
        
    }
    
    
/**
     * select u.id, u.status, p.phonenumber, upper(u.name) nameUpper, a.id, a.topic
     * from User u
     * join u.phonenumbers p
     * join u.articles a
     * =
     * select u.id, u.status, p.phonenumber, upper(u.name) as u__0, a.id, a.topic
     * from USERS u
     * inner join PHONENUMBERS p ON u.id = p.user_id
     * inner join ARTICLES a ON u.id = a.user_id
     * 
     * @dataProvider hydrationModeProvider
     */
    public function testNewHydrationMixedQueryMultipleFetchJoin($hydrationMode)
    {
        // Faked query components
        $queryComponents = array(
            'u' => array(
                'table' => $this->sharedFixture['connection']->getClassMetadata('CmsUser'),
                'mapper' => $this->sharedFixture['connection']->getMapper('CmsUser'),
                'parent' => null,
                'relation' => null,
                'map' => null,
                'agg' => array('0' => 'nameUpper')
                ),
            'p' => array(
                'table' => $this->sharedFixture['connection']->getClassMetadata('CmsPhonenumber'),
                'mapper' => $this->sharedFixture['connection']->getMapper('CmsPhonenumber'),
                'parent' => 'u',
                'relation' => $this->sharedFixture['connection']->getClassMetadata('CmsUser')->getRelation('phonenumbers'),
                'map' => null
                ),
            'a' => array(
                'table' => $this->sharedFixture['connection']->getClassMetadata('CmsArticle'),
                'mapper' => $this->sharedFixture['connection']->getMapper('CmsArticle'),
                'parent' => 'u',
                'relation' => $this->sharedFixture['connection']->getClassMetadata('CmsUser')->getRelation('articles'),
                'map' => null
                ),
            );
        
        // Faked table alias map
        $tableAliasMap = array(
            'u' => 'u',
            'p' => 'p',
            'a' => 'a'
            );
        
        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__0' => 'ROMANB',
                'p__phonenumber' => '42',
                'a__id' => '1',
                'a__topic' => 'Getting things done!'
                ),
           array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__0' => 'ROMANB',
                'p__phonenumber' => '43',
                'a__id' => '1',
                'a__topic' => 'Getting things done!'
                ),
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__0' => 'ROMANB',
                'p__phonenumber' => '42',
                'a__id' => '2',
                'a__topic' => 'ZendCon'
                ),
           array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__0' => 'ROMANB',
                'p__phonenumber' => '43',
                'a__id' => '2',
                'a__topic' => 'ZendCon'
                ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'u__0' => 'JWAGE',
                'p__phonenumber' => '91',
                'a__id' => '3',
                'a__topic' => 'LINQ'
                ),
           array(
                'u__id' => '2',
                'u__status' => 'developer',
                'u__0' => 'JWAGE',
                'p__phonenumber' => '91',
                'a__id' => '4',
                'a__topic' => 'PHP6'
                ),
            );
            
        $stmt = new Doctrine_HydratorMockStatement($resultSet);
        $hydrator = new Doctrine_HydratorNew();
        $hydrator->setQueryComponents($queryComponents);
        
        $hydrator->setResultMixed(true);
        
        $result = $hydrator->hydrateResultSet($stmt, $tableAliasMap, $hydrationMode);
        if ($hydrationMode == Doctrine::HYDRATE_ARRAY) {
            //var_dump($result);
        }
        
        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));
        
        // first user => 2 phonenumbers, 2 articles
        $this->assertEquals(2, count($result[0][0]['phonenumbers']));
        $this->assertEquals(2, count($result[0][0]['articles']));
        $this->assertEquals('ROMANB', $result[0]['nameUpper']);
        // second user => 1 phonenumber, 2 articles
        $this->assertEquals(1, count($result[1][0]['phonenumbers']));
        $this->assertEquals(2, count($result[1][0]['articles']));
        $this->assertEquals('JWAGE', $result[1]['nameUpper']);
        
        $this->assertEquals(42, $result[0][0]['phonenumbers'][0]['phonenumber']);
        $this->assertEquals(43, $result[0][0]['phonenumbers'][1]['phonenumber']);
        $this->assertEquals(91, $result[1][0]['phonenumbers'][0]['phonenumber']);
        
        $this->assertEquals('Getting things done!', $result[0][0]['articles'][0]['topic']);
        $this->assertEquals('ZendCon', $result[0][0]['articles'][1]['topic']);
        $this->assertEquals('LINQ', $result[1][0]['articles'][0]['topic']);
        $this->assertEquals('PHP6', $result[1][0]['articles'][1]['topic']);
        
        if ($hydrationMode == Doctrine::HYDRATE_RECORD) {
            $this->assertTrue($result[0][0] instanceof Doctrine_Record);
            $this->assertTrue($result[0][0]['phonenumbers'] instanceof Doctrine_Collection);
            $this->assertTrue($result[0][0]['phonenumbers'][0] instanceof Doctrine_Record);
            $this->assertTrue($result[0][0]['phonenumbers'][1] instanceof Doctrine_Record);
            $this->assertTrue($result[0][0]['articles'] instanceof Doctrine_Collection);
            $this->assertTrue($result[0][0]['articles'][0] instanceof Doctrine_Record);
            $this->assertTrue($result[0][0]['articles'][1] instanceof Doctrine_Record);
            $this->assertTrue($result[1][0] instanceof Doctrine_Record);
            $this->assertTrue($result[1][0]['phonenumbers'] instanceof Doctrine_Collection);
            $this->assertTrue($result[1][0]['phonenumbers'][0] instanceof Doctrine_Record);
            $this->assertTrue($result[1][0]['articles'][0] instanceof Doctrine_Record);
            $this->assertTrue($result[1][0]['articles'][1] instanceof Doctrine_Record);
        }
    }
    
    
    
    
    
}