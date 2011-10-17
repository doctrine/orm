<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\Tests\Mocks\HydratorMockStatement;
use Doctrine\ORM\Query\ResultSetMapping;

require_once __DIR__ . '/../../TestInit.php';

class ArrayHydratorTest extends HydrationTestCase
{
    /**
     * Select u.id, u.name from Doctrine\Tests\Models\CMS\CmsUser u
     */
    public function testSimpleEntityQuery()
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
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);

        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('romanb', $result[0]['name']);
        $this->assertEquals(2, $result[1]['id']);
        $this->assertEquals('jwage', $result[1]['name']);
    }

    /**
     * 
     */
    public function testSimpleMultipleRootEntityQuery()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsArticle', 'a');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');
        $rsm->addFieldResult('a', 'a__id', 'id');
        $rsm->addFieldResult('a', 'a__topic', 'topic');

        // Faked result set
        $resultSet = array(
            array(
                'u__id' => '1',
                'u__name' => 'romanb',
                'a__id' => '1',
                'a__topic' => 'Cool things.'
                ),
            array(
                'u__id' => '2',
                'u__name' => 'jwage',
                'a__id' => '2',
                'a__topic' => 'Cool things II.'
                )
            );


        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);

        $this->assertEquals(4, count($result));

        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('romanb', $result[0]['name']);
        $this->assertEquals(1, $result[1]['id']);
        $this->assertEquals('Cool things.', $result[1]['topic']);
        $this->assertEquals(2, $result[2]['id']);
        $this->assertEquals('jwage', $result[2]['name']);
        $this->assertEquals(2, $result[3]['id']);
        $this->assertEquals('Cool things II.', $result[3]['topic']);
    }

    /**
     * select u.id, u.status, p.phonenumber, upper(u.name) nameUpper from User u
     * join u.phonenumbers p
     * =
     * select u.id, u.status, p.phonenumber, upper(u.name) as u__0 from USERS u
     * INNER JOIN PHONENUMBERS p ON u.id = p.user_id
     */
    public function testMixedQueryFetchJoin()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\CMS\CmsPhonenumber', 'p',
                'u', 'phonenumbers');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('sclr0', 'nameUpper');
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');

        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '42',
                ),
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '43',
                ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => '91'
                )
            );

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);

        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));

        // first user => 2 phonenumbers
        $this->assertEquals(2, count($result[0][0]['phonenumbers']));
        $this->assertEquals('ROMANB', $result[0]['nameUpper']);
        // second user => 1 phonenumber
        $this->assertEquals(1, count($result[1][0]['phonenumbers']));
        $this->assertEquals('JWAGE', $result[1]['nameUpper']);

        $this->assertEquals(42, $result[0][0]['phonenumbers'][0]['phonenumber']);
        $this->assertEquals(43, $result[0][0]['phonenumbers'][1]['phonenumber']);
        $this->assertEquals(91, $result[1][0]['phonenumbers'][0]['phonenumber']);
    }

    /**
     * select u.id, u.status, count(p.phonenumber) numPhones from User u
     * join u.phonenumbers p group by u.status, u.id
     * =
     * select u.id, u.status, count(p.phonenumber) as p__0 from USERS u
     * INNER JOIN PHONENUMBERS p ON u.id = p.user_id group by u.id, u.status
     */
    public function testMixedQueryNormalJoin()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('sclr0', 'numPhones');

        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => '2',
                ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => '1',
                )
            );

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);

        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));
        // first user => 2 phonenumbers
        $this->assertEquals(2, $result[0]['numPhones']);
        // second user => 1 phonenumber
        $this->assertEquals(1, $result[1]['numPhones']);
    }

    /**
     * select u.id, u.status, upper(u.name) nameUpper from User u index by u.id
     * join u.phonenumbers p indexby p.phonenumber
     * =
     * select u.id, u.status, upper(u.name) as p__0 from USERS u
     * INNER JOIN PHONENUMBERS p ON u.id = p.user_id
     */
    public function testMixedQueryFetchJoinCustomIndex()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\CMS\CmsPhonenumber',
                'p',
                'u',
                'phonenumbers'
        );
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('sclr0', 'nameUpper');
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');
        $rsm->addIndexBy('u', 'id');
        $rsm->addIndexBy('p', 'phonenumber');

        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '42',
                ),
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '43',
                ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => '91'
                )
            );


        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);

        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));

        // test the scalar values
        $this->assertEquals('ROMANB', $result[0]['nameUpper']);
        $this->assertEquals('JWAGE', $result[1]['nameUpper']);
        // first user => 2 phonenumbers. notice the custom indexing by user id
        $this->assertEquals(2, count($result[0]['1']['phonenumbers']));
        // second user => 1 phonenumber. notice the custom indexing by user id
        $this->assertEquals(1, count($result[1]['2']['phonenumbers']));
        // test the custom indexing of the phonenumbers
        $this->assertTrue(isset($result[0]['1']['phonenumbers']['42']));
        $this->assertTrue(isset($result[0]['1']['phonenumbers']['43']));
        $this->assertTrue(isset($result[1]['2']['phonenumbers']['91']));
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
    public function testMixedQueryMultipleFetchJoin()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\CMS\CmsPhonenumber',
                'p',
                'u',
                'phonenumbers'
        );
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\CMS\CmsArticle',
                'a',
                'u',
                'articles'
        );
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('sclr0', 'nameUpper');
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');
        $rsm->addFieldResult('a', 'a__id', 'id');
        $rsm->addFieldResult('a', 'a__topic', 'topic');

        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '42',
                'a__id' => '1',
                'a__topic' => 'Getting things done!'
                ),
           array(
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '43',
                'a__id' => '1',
                'a__topic' => 'Getting things done!'
                ),
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '42',
                'a__id' => '2',
                'a__topic' => 'ZendCon'
                ),
           array(
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '43',
                'a__id' => '2',
                'a__topic' => 'ZendCon'
                ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => '91',
                'a__id' => '3',
                'a__topic' => 'LINQ'
                ),
           array(
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => '91',
                'a__id' => '4',
                'a__topic' => 'PHP6'
                ),
            );

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);

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
    public function testMixedQueryMultipleDeepMixedFetchJoin()
    {

        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\CMS\CmsPhonenumber',
                'p',
                'u',
                'phonenumbers'
        );
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\CMS\CmsArticle',
                'a',
                'u',
                'articles'
        );
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\CMS\CmsComment',
                'c',
                'a',
                'comments'
        );
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('sclr0', 'nameUpper');
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');
        $rsm->addFieldResult('a', 'a__id', 'id');
        $rsm->addFieldResult('a', 'a__topic', 'topic');
        $rsm->addFieldResult('c', 'c__id', 'id');
        $rsm->addFieldResult('c', 'c__topic', 'topic');

        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '42',
                'a__id' => '1',
                'a__topic' => 'Getting things done!',
                'c__id' => '1',
                'c__topic' => 'First!'
                ),
           array(
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '43',
                'a__id' => '1',
                'a__topic' => 'Getting things done!',
                'c__id' => '1',
                'c__topic' => 'First!'
                ),
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '42',
                'a__id' => '2',
                'a__topic' => 'ZendCon',
                'c__id' => null,
                'c__topic' => null
                ),
           array(
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '43',
                'a__id' => '2',
                'a__topic' => 'ZendCon',
                'c__id' => null,
                'c__topic' => null
                ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => '91',
                'a__id' => '3',
                'a__topic' => 'LINQ',
                'c__id' => null,
                'c__topic' => null
                ),
           array(
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => '91',
                'a__id' => '4',
                'a__topic' => 'PHP6',
                'c__id' => null,
                'c__topic' => null
                ),
            );

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);

        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));

        // first user => 2 phonenumbers, 2 articles, 1 comment on first article
        $this->assertEquals(2, count($result[0][0]['phonenumbers']));
        $this->assertEquals(2, count($result[0][0]['articles']));
        $this->assertEquals(1, count($result[0][0]['articles'][0]['comments']));
        $this->assertEquals('ROMANB', $result[0]['nameUpper']);
        // second user => 1 phonenumber, 2 articles, no comments
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

        $this->assertEquals('First!', $result[0][0]['articles'][0]['comments'][0]['topic']);

        $this->assertTrue(isset($result[0][0]['articles'][0]['comments']));

        // empty comment collections
        $this->assertTrue(is_array($result[0][0]['articles'][1]['comments']));
        $this->assertEquals(0, count($result[0][0]['articles'][1]['comments']));
        $this->assertTrue(is_array($result[1][0]['articles'][0]['comments']));
        $this->assertEquals(0, count($result[1][0]['articles'][0]['comments']));
        $this->assertTrue(is_array($result[1][0]['articles'][1]['comments']));
        $this->assertEquals(0, count($result[1][0]['articles'][1]['comments']));
    }

    /**
     * Tests that the hydrator does not rely on a particular order of the rows
     * in the result set.
     *
     * DQL:
     * select c.id, c.position, c.name, b.id, b.position
     * from \Doctrine\Tests\Models\Forum\ForumCategory c inner join c.boards b
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
    public function testEntityQueryCustomResultSetOrder()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\Forum\ForumCategory', 'c');
        $rsm->addJoinedEntityResult(
                'Doctrine\Tests\Models\Forum\ForumBoard',
                'b',
                'c',
                'boards'
        );
        $rsm->addFieldResult('c', 'c__id', 'id');
        $rsm->addFieldResult('c', 'c__position', 'position');
        $rsm->addFieldResult('c', 'c__name', 'name');
        $rsm->addFieldResult('b', 'b__id', 'id');
        $rsm->addFieldResult('b', 'b__position', 'position');

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

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);

        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));
        $this->assertTrue(isset($result[0]['boards']));
        $this->assertEquals(3, count($result[0]['boards']));
        $this->assertTrue(isset($result[1]['boards']));
        $this->assertEquals(1, count($result[1]['boards']));
    }
    
    /**
     * DQL: select partial u.{id,status}, a.id, a.topic, c.id as cid, c.topic as ctopic from CmsUser u left join u.articles a left join a.comments c
     * 
     */
    /*public function testChainedJoinWithScalars()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('a__id', 'id');
        $rsm->addScalarResult('a__topic', 'topic');
        $rsm->addScalarResult('c__id', 'cid');
        $rsm->addScalarResult('c__topic', 'ctopic');

        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'a__id' => '1',
                'a__topic' => 'The First',
                'c__id' => '1',
                'c__topic' => 'First Comment'
                ),
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'a__id' => '1',
                'a__topic' => 'The First',
                'c__id' => '2',
                'c__topic' => 'Second Comment'
                ),
           array(
                'u__id' => '1',
                'u__status' => 'developer',
                'a__id' => '42',
                'a__topic' => 'The Answer',
                'c__id' => null,
                'c__topic' => null
                ),
            );

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);
        
        $this->assertEquals(3, count($result));
        
        $this->assertEquals(2, count($result[0][0])); // User array
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('The First', $result[0]['topic']);
        $this->assertEquals(1, $result[0]['cid']);
        $this->assertEquals('First Comment', $result[0]['ctopic']);
        
        $this->assertEquals(2, count($result[1][0])); // User array, duplicated
        $this->assertEquals(1, $result[1]['id']); // duplicated
        $this->assertEquals('The First', $result[1]['topic']); // duplicated
        $this->assertEquals(2, $result[1]['cid']);
        $this->assertEquals('Second Comment', $result[1]['ctopic']);
        
        $this->assertEquals(2, count($result[2][0])); // User array, duplicated
        $this->assertEquals(42, $result[2]['id']);
        $this->assertEquals('The Answer', $result[2]['topic']);
        $this->assertNull($result[2]['cid']);
        $this->assertNull($result[2]['ctopic']);
    }*/

    public function testResultIteration()
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
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this->_em);

        $iterableResult = $hydrator->iterate($stmt, $rsm);

        $rowNum = 0;
        while (($row = $iterableResult->next()) !== false) {
            $this->assertEquals(1, count($row));
            $this->assertTrue(is_array($row[0]));
            if ($rowNum == 0) {
                $this->assertEquals(1, $row[0]['id']);
                $this->assertEquals('romanb', $row[0]['name']);
            } else if ($rowNum == 1) {
                $this->assertEquals(2, $row[0]['id']);
                $this->assertEquals('jwage', $row[0]['name']);
            }
            ++$rowNum;
        }
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

        // Faked result set
        $resultSet = array(
            array(
                'u__id' => '1',
                'u__name' => 'romanb',
                'foo' => 'bar', // unknown!
                ),
        );

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);

        $this->assertEquals(1, count($result));
    }

    /**
     * @group DDC-1358
     */
    public function testMissingIdForRootEntity()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('sclr0', 'nameUpper');

        // Faked result set
        $resultSet = array(
            //row1
            array(
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                ),
            array(
                'u__id' => null,
                'u__status' => null,
                'sclr0' => 'ROMANB',
                ),
            array(
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
                ),
            array(
                'u__id' => null,
                'u__status' => null,
                'sclr0' => 'JWAGE',
                ),
            );

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this->_em);

        $result = $hydrator->hydrateAll($stmt, $rsm);

        $this->assertEquals(4, count($result), "Should hydrate four results.");

        $this->assertEquals('ROMANB', $result[0]['nameUpper']);
        $this->assertEquals('ROMANB', $result[1]['nameUpper']);
        $this->assertEquals('JWAGE', $result[2]['nameUpper']);
        $this->assertEquals('JWAGE', $result[3]['nameUpper']);

        $this->assertEquals(array('id' => 1, 'status' => 'developer'), $result[0][0]);
        $this->assertNull($result[1][0]);
        $this->assertEquals(array('id' => 2, 'status' => 'developer'), $result[2][0]);
        $this->assertNull($result[3][0]);
    }
}