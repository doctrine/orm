<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'HydrationTest.php';

/**
 * Description of ObjectHydratorTest
 *
 * @author robo
 */
class Orm_Hydration_ObjectHydratorTest extends Orm_Hydration_HydrationTest
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
        $hydrator = new Doctrine_ORM_Internal_Hydration_ObjectHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $this->_createParserResult(
                $stmt, $queryComponents, $tableAliasMap, Doctrine_ORM_Query::HYDRATE_OBJECT));

        $this->assertEquals(2, count($result));
        $this->assertTrue($result instanceof Doctrine_ORM_Collection);
        $this->assertTrue($result[0] instanceof CmsUser);
        $this->assertTrue($result[1] instanceof CmsUser);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('romanb', $result[0]->name);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals('jwage', $result[1]->name);
    }

    /**
     * select u.id, u.status, p.phonenumber, upper(u.name) nameUpper from User u
     * join u.phonenumbers p
     * =
     * select u.id, u.status, p.phonenumber, upper(u.name) as u__0 from USERS u
     * INNER JOIN PHONENUMBERS p ON u.id = p.user_id
     */
    public function testNewHydrationMixedQueryFetchJoin()
    {
        // Faked query components
        $queryComponents = array(
            'u' => array(
                'metadata' => $this->_em->getClassMetadata('CmsUser'),
                'parent' => null,
                'relation' => null,
                'map' => null,
                'agg' => array('0' => 'nameUpper')
                ),
            'p' => array(
                'metadata' => $this->_em->getClassMetadata('CmsPhonenumber'),
                'parent' => 'u',
                'relation' => $this->_em->getClassMetadata('CmsUser')->getAssociationMapping('phonenumbers'),
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
        $hydrator = new Doctrine_ORM_Internal_Hydration_ObjectHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $this->_createParserResult(
                $stmt, $queryComponents, $tableAliasMap, Doctrine_ORM_Query::HYDRATE_OBJECT, true));

        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));

        $this->assertTrue($result[0][0] instanceof CmsUser);
        $this->assertTrue($result[0][0]->phonenumbers instanceof Doctrine_ORM_Collection);
        $this->assertTrue($result[0][0]->phonenumbers[0] instanceof CmsPhonenumber);
        $this->assertTrue($result[0][0]->phonenumbers[1] instanceof CmsPhonenumber);
        $this->assertTrue($result[1][0] instanceof CmsUser);
        $this->assertTrue($result[1][0]->phonenumbers instanceof Doctrine_ORM_Collection);

        // first user => 2 phonenumbers
        $this->assertEquals(2, count($result[0][0]->phonenumbers));
        $this->assertEquals('ROMANB', $result[0]['nameUpper']);
        // second user => 1 phonenumber
        $this->assertEquals(1, count($result[1][0]->phonenumbers));
        $this->assertEquals('JWAGE', $result[1]['nameUpper']);

        $this->assertEquals(42, $result[0][0]->phonenumbers[0]->phonenumber);
        $this->assertEquals(43, $result[0][0]->phonenumbers[1]->phonenumber);
        $this->assertEquals(91, $result[1][0]->phonenumbers[0]->phonenumber);
    }

    /**
     * select u.id, u.status, count(p.phonenumber) numPhones from User u
     * join u.phonenumbers p group by u.status, u.id
     * =
     * select u.id, u.status, count(p.phonenumber) as p__0 from USERS u
     * INNER JOIN PHONENUMBERS p ON u.id = p.user_id group by u.id, u.status
     */
    public function testNewHydrationMixedQueryNormalJoin()
    {
        // Faked query components
        $queryComponents = array(
            'u' => array(
                'metadata' => $this->_em->getClassMetadata('CmsUser'),
                'parent' => null,
                'relation' => null,
                'map' => null
                ),
            'p' => array(
                'metadata' => $this->_em->getClassMetadata('CmsPhonenumber'),
                'parent' => 'u',
                'relation' => $this->_em->getClassMetadata('CmsUser')->getAssociationMapping('phonenumbers'),
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
        $hydrator = new Doctrine_ORM_Internal_Hydration_ObjectHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $this->_createParserResult(
                $stmt, $queryComponents, $tableAliasMap, Doctrine_ORM_Query::HYDRATE_OBJECT, true));

        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));
        // first user => 2 phonenumbers
        $this->assertEquals(2, $result[0]['numPhones']);
        // second user => 1 phonenumber
        $this->assertEquals(1, $result[1]['numPhones']);
        $this->assertTrue($result[0][0] instanceof CmsUser);
        $this->assertTrue($result[1][0] instanceof CmsUser);
    }

    /**
     * select u.id, u.status, upper(u.name) nameUpper from User u index by u.id
     * join u.phonenumbers p indexby p.phonenumber
     * =
     * select u.id, u.status, upper(u.name) as p__0 from USERS u
     * INNER JOIN PHONENUMBERS p ON u.id = p.user_id
     */
    public function testNewHydrationMixedQueryFetchJoinCustomIndex()
    {
        // Faked query components
        $queryComponents = array(
            'u' => array(
                'metadata' => $this->_em->getClassMetadata('CmsUser'),
                'parent' => null,
                'relation' => null,
                'agg' => array('0' => 'nameUpper'),
                'map' => 'id'
                ),
            'p' => array(
                'metadata' => $this->_em->getClassMetadata('CmsPhonenumber'),
                'parent' => 'u',
                'relation' => $this->_em->getClassMetadata('CmsUser')->getAssociationMapping('phonenumbers'),
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
        $hydrator = new Doctrine_ORM_Internal_Hydration_ObjectHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $this->_createParserResult(
                $stmt, $queryComponents, $tableAliasMap, Doctrine_ORM_Query::HYDRATE_OBJECT, true));

        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));

        // test the scalar values
        $this->assertEquals('ROMANB', $result[0]['nameUpper']);
        $this->assertEquals('JWAGE', $result[1]['nameUpper']);

        $this->assertTrue($result[0]['1'] instanceof CmsUser);
        $this->assertTrue($result[1]['2'] instanceof CmsUser);
        $this->assertTrue($result[0]['1']->phonenumbers instanceof Doctrine_ORM_Collection);
        // first user => 2 phonenumbers. notice the custom indexing by user id
        $this->assertEquals(2, count($result[0]['1']->phonenumbers));
        // second user => 1 phonenumber. notice the custom indexing by user id
        $this->assertEquals(1, count($result[1]['2']->phonenumbers));
        // test the custom indexing of the phonenumbers
        $this->assertTrue(isset($result[0]['1']->phonenumbers['42']));
        $this->assertTrue(isset($result[0]['1']->phonenumbers['43']));
        $this->assertTrue(isset($result[1]['2']->phonenumbers['91']));
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
     */
    public function testNewHydrationMixedQueryMultipleFetchJoin()
    {
        // Faked query components
        $queryComponents = array(
            'u' => array(
                'metadata' => $this->_em->getClassMetadata('CmsUser'),
                'parent' => null,
                'relation' => null,
                'map' => null,
                'agg' => array('0' => 'nameUpper')
                ),
            'p' => array(
                'metadata' => $this->_em->getClassMetadata('CmsPhonenumber'),
                'parent' => 'u',
                'relation' => $this->_em->getClassMetadata('CmsUser')->getAssociationMapping('phonenumbers'),
                'map' => null
                ),
            'a' => array(
                'metadata' => $this->_em->getClassMetadata('CmsArticle'),
                'parent' => 'u',
                'relation' => $this->_em->getClassMetadata('CmsUser')->getAssociationMapping('articles'),
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
        $hydrator = new Doctrine_ORM_Internal_Hydration_ObjectHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $this->_createParserResult(
                $stmt, $queryComponents, $tableAliasMap, Doctrine_ORM_Query::HYDRATE_OBJECT, true));

        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));

        $this->assertTrue($result[0][0] instanceof CmsUser);
        $this->assertTrue($result[0][0]->phonenumbers instanceof Doctrine_ORM_Collection);
        $this->assertTrue($result[0][0]->phonenumbers[0] instanceof CmsPhonenumber);
        $this->assertTrue($result[0][0]->phonenumbers[1] instanceof CmsPhonenumber);
        $this->assertTrue($result[0][0]->articles instanceof Doctrine_ORM_Collection);
        $this->assertTrue($result[0][0]->articles[0] instanceof CmsArticle);
        $this->assertTrue($result[0][0]->articles[1] instanceof CmsArticle);
        $this->assertTrue($result[1][0] instanceof CmsUser);
        $this->assertTrue($result[1][0]->phonenumbers instanceof Doctrine_ORM_Collection);
        $this->assertTrue($result[1][0]->phonenumbers[0] instanceof CmsPhonenumber);
        $this->assertTrue($result[1][0]->articles[0] instanceof CmsArticle);
        $this->assertTrue($result[1][0]->articles[1] instanceof CmsArticle);
    }

    /**
     * select u.id, u.status, p.phonenumber, upper(u.name) nameUpper, a.id, a.topic,
     * c.id, c.topic
     * from User u
     * join u.phonenumbers p
     * join u.articles a
     * left join a.comments c
     * =
     * select u.id, u.status, p.phonenumber, upper(u.name) as u__0, a.id, a.topic,
     * c.id, c.topic
     * from USERS u
     * inner join PHONENUMBERS p ON u.id = p.user_id
     * inner join ARTICLES a ON u.id = a.user_id
     * left outer join COMMENTS c ON a.id = c.article_id
     */
    public function testNewHydrationMixedQueryMultipleDeepMixedFetchJoin()
    {
        // Faked query components
        $queryComponents = array(
            'u' => array(
                'metadata' => $this->_em->getClassMetadata('CmsUser'),
                'parent' => null,
                'relation' => null,
                'map' => null,
                'agg' => array('0' => 'nameUpper')
                ),
            'p' => array(
                'metadata' => $this->_em->getClassMetadata('CmsPhonenumber'),
                'parent' => 'u',
                'relation' => $this->_em->getClassMetadata('CmsUser')->getAssociationMapping('phonenumbers'),
                'map' => null
                ),
            'a' => array(
                'metadata' => $this->_em->getClassMetadata('CmsArticle'),
                'parent' => 'u',
                'relation' => $this->_em->getClassMetadata('CmsUser')->getAssociationMapping('articles'),
                'map' => null
                ),
            'c' => array(
                'metadata' => $this->_em->getClassMetadata('CmsComment'),
                'parent' => 'a',
                'relation' => $this->_em->getClassMetadata('CmsArticle')->getAssociationMapping('comments'),
                'map' => null
                ),
            );

        // Faked table alias map
        $tableAliasMap = array(
            'u' => 'u',
            'p' => 'p',
            'a' => 'a',
            'c' => 'c'
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
                'a__topic' => 'Getting things done!',
                'c__id' => '1',
                'c__topic' => 'First!'
                ),
           array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__0' => 'ROMANB',
                'p__phonenumber' => '43',
                'a__id' => '1',
                'a__topic' => 'Getting things done!',
                'c__id' => '1',
                'c__topic' => 'First!'
                ),
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__0' => 'ROMANB',
                'p__phonenumber' => '42',
                'a__id' => '2',
                'a__topic' => 'ZendCon',
                'c__id' => null,
                'c__topic' => null
                ),
           array(
                'u__id' => '1',
                'u__status' => 'developer',
                'u__0' => 'ROMANB',
                'p__phonenumber' => '43',
                'a__id' => '2',
                'a__topic' => 'ZendCon',
                'c__id' => null,
                'c__topic' => null
                ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'u__0' => 'JWAGE',
                'p__phonenumber' => '91',
                'a__id' => '3',
                'a__topic' => 'LINQ',
                'c__id' => null,
                'c__topic' => null
                ),
           array(
                'u__id' => '2',
                'u__status' => 'developer',
                'u__0' => 'JWAGE',
                'p__phonenumber' => '91',
                'a__id' => '4',
                'a__topic' => 'PHP6',
                'c__id' => null,
                'c__topic' => null
                ),
            );

        $stmt = new Doctrine_HydratorMockStatement($resultSet);
        $hydrator = new Doctrine_ORM_Internal_Hydration_ObjectHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $this->_createParserResult(
                $stmt, $queryComponents, $tableAliasMap, Doctrine_ORM_Query::HYDRATE_OBJECT, true));

        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));

        $this->assertTrue($result[0][0] instanceof CmsUser);
        $this->assertTrue($result[1][0] instanceof CmsUser);
        // phonenumbers
        $this->assertTrue($result[0][0]->phonenumbers instanceof Doctrine_ORM_Collection);
        $this->assertTrue($result[0][0]->phonenumbers[0] instanceof CmsPhonenumber);
        $this->assertTrue($result[0][0]->phonenumbers[1] instanceof CmsPhonenumber);
        $this->assertTrue($result[1][0]->phonenumbers instanceof Doctrine_ORM_Collection);
        $this->assertTrue($result[1][0]->phonenumbers[0] instanceof CmsPhonenumber);
        // articles
        $this->assertTrue($result[0][0]->articles instanceof Doctrine_ORM_Collection);
        $this->assertTrue($result[0][0]->articles[0] instanceof CmsArticle);
        $this->assertTrue($result[0][0]->articles[1] instanceof CmsArticle);
        $this->assertTrue($result[1][0]->articles[0] instanceof CmsArticle);
        $this->assertTrue($result[1][0]->articles[1] instanceof CmsArticle);
        // article comments
        $this->assertTrue($result[0][0]->articles[0]->comments instanceof Doctrine_ORM_Collection);
        $this->assertTrue($result[0][0]->articles[0]->comments[0] instanceof CmsComment);
        // empty comment collections
        $this->assertTrue($result[0][0]->articles[1]->comments instanceof Doctrine_ORM_Collection);
        $this->assertEquals(0, count($result[0][0]->articles[1]->comments));
        $this->assertTrue($result[1][0]->articles[0]->comments instanceof Doctrine_ORM_Collection);
        $this->assertEquals(0, count($result[1][0]->articles[0]->comments));
        $this->assertTrue($result[1][0]->articles[1]->comments instanceof Doctrine_ORM_Collection);
        $this->assertEquals(0, count($result[1][0]->articles[1]->comments));
    }

    /**
     * Tests that the hydrator does not rely on a particular order of the rows
     * in the result set.
     *
     * DQL:
     * select c.id, c.position, c.name, b.id, b.position
     * from ForumCategory c inner join c.boards b
     * order by c.position asc, b.position asc
     *
     * Checks whether the boards are correctly assigned to the categories.
     *
     * The 'evil' result set that confuses the object population is displayed below.
     *
     * c.id  | c.position | c.name   | boardPos | b.id | b.category_id (just for clarity)
     *  1    | 0          | First    | 0        |   1  | 1
     *  2    | 0          | Second   | 0        |   2  | 2   <--
     *  1    | 0          | First    | 1        |   3  | 1
     *  1    | 0          | First    | 2        |   4  | 1
     */
    public function testNewHydrationEntityQueryCustomResultSetOrder()
    {
        // Faked query components
        $queryComponents = array(
            'c' => array(
                'metadata' => $this->_em->getClassMetadata('ForumCategory'),
                'parent' => null,
                'relation' => null,
                'map' => null
                ),
            'b' => array(
                'metadata' => $this->_em->getClassMetadata('ForumBoard'),
                'parent' => 'c',
                'relation' => $this->_em->getClassMetadata('ForumCategory')->getAssociationMapping('boards'),
                'map' => null
                ),
            );

        // Faked table alias map
        $tableAliasMap = array(
            'c' => 'c',
            'b' => 'b'
            );

        // Faked result set
        $resultSet = array(
            array(
                'c__id' => '1',
                'c__position' => '0',
                'c__name' => 'First',
                'b__id' => '1',
                'b__position' => '0',
                //'b__category_id' => '1'
                ),
           array(
                'c__id' => '2',
                'c__position' => '0',
                'c__name' => 'Second',
                'b__id' => '2',
                'b__position' => '0',
                //'b__category_id' => '2'
                ),
            array(
                'c__id' => '1',
                'c__position' => '0',
                'c__name' => 'First',
                'b__id' => '3',
                'b__position' => '1',
                //'b__category_id' => '1'
                ),
           array(
                'c__id' => '1',
                'c__position' => '0',
                'c__name' => 'First',
                'b__id' => '4',
                'b__position' => '2',
                //'b__category_id' => '1'
                )
            );

        $stmt = new Doctrine_HydratorMockStatement($resultSet);
        $hydrator = new Doctrine_ORM_Internal_Hydration_ObjectHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $this->_createParserResult(
                $stmt, $queryComponents, $tableAliasMap, Doctrine_ORM_Query::HYDRATE_OBJECT));

        $this->assertEquals(2, count($result));
        $this->assertTrue($result instanceof Doctrine_ORM_Collection);
        $this->assertTrue($result[0] instanceof ForumCategory);
        $this->assertTrue($result[1] instanceof ForumCategory);
        $this->assertEquals(1, $result[0]->getId());
        $this->assertEquals(2, $result[1]->getId());
        $this->assertTrue(isset($result[0]->boards));
        $this->assertEquals(3, count($result[0]->boards));
        $this->assertTrue(isset($result[1]->boards));
        $this->assertEquals(1, count($result[1]->boards));

    }

    public function testResultIteration() {
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
        $hydrator = new Doctrine_ORM_Internal_Hydration_ObjectHydrator($this->_em);

        $iterableResult = $hydrator->iterate($stmt, $this->_createParserResult(
                $stmt, $queryComponents, $tableAliasMap, Doctrine_ORM_Query::HYDRATE_OBJECT));

        $rowNum = 0;
        while (($row = $iterableResult->next()) !== false) {
            $this->assertEquals(1, count($row));
            $this->assertTrue($row[0] instanceof CmsUser);
            if ($rowNum == 0) {
                $this->assertEquals(1, $row[0]->id);
                $this->assertEquals('romanb', $row[0]->name);
            } else if ($rowNum == 1) {
                $this->assertEquals(2, $row[0]->id);
                $this->assertEquals('jwage', $row[0]->name);
            }
            ++$rowNum;
        }
    }
}

