<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FetchMode;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Mocks\HydratorMockStatement;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsComment;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\Company\CompanyPerson;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceShipping;
use Doctrine\Tests\Models\Forum\ForumBoard;
use Doctrine\Tests\Models\Forum\ForumCategory;
use Doctrine\Tests\Models\Hydration\EntityWithArrayDefaultArrayValueM2M;
use Doctrine\Tests\Models\Hydration\SimpleEntity;

class ObjectHydratorTest extends HydrationTestCase
{
    public function provideDataForUserEntityResult()
    {
        return [
            [0],
            ['user'],
        ];
    }

    public function provideDataForMultipleRootEntityResult()
    {
        return [
            [0, 0],
            ['user', 0],
            [0, 'article'],
            ['user', 'article'],
        ];
    }

    public function provideDataForProductEntityResult()
    {
        return [
            [0],
            ['product'],
        ];
    }

    /**
     * SELECT PARTIAL u.{id,name}
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     */
    public function testSimpleEntityQuery()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        // Faked result set
        $resultSet = [
            [
                'u__id' => '1',
                'u__name' => 'romanb'
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage'
            ]
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertInstanceOf(CmsUser::class, $result[0]);
        self::assertInstanceOf(CmsUser::class, $result[1]);

        self::assertEquals(1, $result[0]->id);
        self::assertEquals('romanb', $result[0]->name);

        self::assertEquals(2, $result[1]->id);
        self::assertEquals('jwage', $result[1]->name);
    }

    /**
     * SELECT PARTIAL u.{id,name} AS user
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     */
    public function testSimpleEntityQueryWithAliasedUserEntity()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', 'user');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        // Faked result set
        $resultSet = [
            [
                'u__id' => '1',
                'u__name' => 'romanb'
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage'
            ]
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertArrayHasKey('user', $result[0]);
        self::assertInstanceOf(CmsUser::class, $result[0]['user']);

        self::assertArrayHasKey('user', $result[1]);
        self::assertInstanceOf(CmsUser::class, $result[1]['user']);

        self::assertEquals(1, $result[0]['user']->id);
        self::assertEquals('romanb', $result[0]['user']->name);

        self::assertEquals(2, $result[1]['user']->id);
        self::assertEquals('jwage', $result[1]['user']->name);
    }

    /**
     * SELECT PARTIAL u.{id, name}, PARTIAL a.{id, topic}
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u, Doctrine\Tests\Models\CMS\CmsArticle a
     */
    public function testSimpleMultipleRootEntityQuery()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addEntityResult(CmsArticle::class, 'a');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');
        $rsm->addFieldResult('a', 'a__id', 'id');
        $rsm->addFieldResult('a', 'a__topic', 'topic');

        // Faked result set
        $resultSet = [
            [
                'u__id' => '1',
                'u__name' => 'romanb',
                'a__id' => '1',
                'a__topic' => 'Cool things.'
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'a__id' => '2',
                'a__topic' => 'Cool things II.'
            ]
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(4, count($result));

        self::assertInstanceOf(CmsUser::class, $result[0]);
        self::assertInstanceOf(CmsArticle::class, $result[1]);
        self::assertInstanceOf(CmsUser::class, $result[2]);
        self::assertInstanceOf(CmsArticle::class, $result[3]);

        self::assertEquals(1, $result[0]->id);
        self::assertEquals('romanb', $result[0]->name);

        self::assertEquals(1, $result[1]->id);
        self::assertEquals('Cool things.', $result[1]->topic);

        self::assertEquals(2, $result[2]->id);
        self::assertEquals('jwage', $result[2]->name);

        self::assertEquals(2, $result[3]->id);
        self::assertEquals('Cool things II.', $result[3]->topic);
    }

    /**
     * SELECT PARTIAL u.{id, name} AS user, PARTIAL a.{id, topic}
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u, Doctrine\Tests\Models\CMS\CmsArticle a
     */
    public function testSimpleMultipleRootEntityQueryWithAliasedUserEntity()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', 'user');
        $rsm->addEntityResult(CmsArticle::class, 'a');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');
        $rsm->addFieldResult('a', 'a__id', 'id');
        $rsm->addFieldResult('a', 'a__topic', 'topic');

        // Faked result set
        $resultSet = [
            [
                'u__id' => '1',
                'u__name' => 'romanb',
                'a__id' => '1',
                'a__topic' => 'Cool things.'
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'a__id' => '2',
                'a__topic' => 'Cool things II.'
            ]
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(4, count($result));

        self::assertArrayHasKey('user', $result[0]);
        self::assertArrayNotHasKey(0, $result[0]);
        self::assertInstanceOf(CmsUser::class, $result[0]['user']);
        self::assertEquals(1, $result[0]['user']->id);
        self::assertEquals('romanb', $result[0]['user']->name);

        self::assertArrayHasKey(0, $result[1]);
        self::assertArrayNotHasKey('user', $result[1]);
        self::assertInstanceOf(CmsArticle::class, $result[1][0]);
        self::assertEquals(1, $result[1][0]->id);
        self::assertEquals('Cool things.', $result[1][0]->topic);

        self::assertArrayHasKey('user', $result[2]);
        self::assertArrayNotHasKey(0, $result[2]);
        self::assertInstanceOf(CmsUser::class, $result[2]['user']);
        self::assertEquals(2, $result[2]['user']->id);
        self::assertEquals('jwage', $result[2]['user']->name);

        self::assertArrayHasKey(0, $result[3]);
        self::assertArrayNotHasKey('user', $result[3]);
        self::assertInstanceOf(CmsArticle::class, $result[3][0]);
        self::assertEquals(2, $result[3][0]->id);
        self::assertEquals('Cool things II.', $result[3][0]->topic);
    }

    /**
     * SELECT PARTIAL u.{id, name}, PARTIAL a.{id, topic} AS article
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u, Doctrine\Tests\Models\CMS\CmsArticle a
     */
    public function testSimpleMultipleRootEntityQueryWithAliasedArticleEntity()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addEntityResult(CmsArticle::class, 'a', 'article');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');
        $rsm->addFieldResult('a', 'a__id', 'id');
        $rsm->addFieldResult('a', 'a__topic', 'topic');

        // Faked result set
        $resultSet = [
            [
                'u__id' => '1',
                'u__name' => 'romanb',
                'a__id' => '1',
                'a__topic' => 'Cool things.'
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'a__id' => '2',
                'a__topic' => 'Cool things II.'
            ]
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(4, count($result));

        self::assertArrayHasKey(0, $result[0]);
        self::assertArrayNotHasKey('article', $result[0]);
        self::assertInstanceOf(CmsUser::class, $result[0][0]);
        self::assertEquals(1, $result[0][0]->id);
        self::assertEquals('romanb', $result[0][0]->name);

        self::assertArrayHasKey('article', $result[1]);
        self::assertArrayNotHasKey(0, $result[1]);
        self::assertInstanceOf(CmsArticle::class, $result[1]['article']);
        self::assertEquals(1, $result[1]['article']->id);
        self::assertEquals('Cool things.', $result[1]['article']->topic);

        self::assertArrayHasKey(0, $result[2]);
        self::assertArrayNotHasKey('article', $result[2]);
        self::assertInstanceOf(CmsUser::class, $result[2][0]);
        self::assertEquals(2, $result[2][0]->id);
        self::assertEquals('jwage', $result[2][0]->name);

        self::assertArrayHasKey('article', $result[3]);
        self::assertArrayNotHasKey(0, $result[3]);
        self::assertInstanceOf(CmsArticle::class, $result[3]['article']);
        self::assertEquals(2, $result[3]['article']->id);
        self::assertEquals('Cool things II.', $result[3]['article']->topic);
    }

    /**
     * SELECT PARTIAL u.{id, name} AS user, PARTIAL a.{id, topic} AS article
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u, Doctrine\Tests\Models\CMS\CmsArticle a
     */
    public function testSimpleMultipleRootEntityQueryWithAliasedEntities()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', 'user');
        $rsm->addEntityResult(CmsArticle::class, 'a', 'article');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');
        $rsm->addFieldResult('a', 'a__id', 'id');
        $rsm->addFieldResult('a', 'a__topic', 'topic');

        // Faked result set
        $resultSet = [
            [
                'u__id' => '1',
                'u__name' => 'romanb',
                'a__id' => '1',
                'a__topic' => 'Cool things.'
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'a__id' => '2',
                'a__topic' => 'Cool things II.'
            ]
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(4, count($result));

        self::assertArrayHasKey('user', $result[0]);
        self::assertArrayNotHasKey('article', $result[0]);
        self::assertInstanceOf(CmsUser::class, $result[0]['user']);
        self::assertEquals(1, $result[0]['user']->id);
        self::assertEquals('romanb', $result[0]['user']->name);

        self::assertArrayHasKey('article', $result[1]);
        self::assertArrayNotHasKey('user', $result[1]);
        self::assertInstanceOf(CmsArticle::class, $result[1]['article']);
        self::assertEquals(1, $result[1]['article']->id);
        self::assertEquals('Cool things.', $result[1]['article']->topic);

        self::assertArrayHasKey('user', $result[2]);
        self::assertArrayNotHasKey('article', $result[2]);
        self::assertInstanceOf(CmsUser::class, $result[2]['user']);
        self::assertEquals(2, $result[2]['user']->id);
        self::assertEquals('jwage', $result[2]['user']->name);

        self::assertArrayHasKey('article', $result[3]);
        self::assertArrayNotHasKey('user', $result[3]);
        self::assertInstanceOf(CmsArticle::class, $result[3]['article']);
        self::assertEquals(2, $result[3]['article']->id);
        self::assertEquals('Cool things II.', $result[3]['article']->topic);
    }

    /**
     * SELECT PARTIAL u.{id, status}, COUNT(p.phonenumber) numPhones
     *   FROM User u
     *   JOIN u.phonenumbers p
     *  GROUP BY u.id
     *
     * @dataProvider provideDataForUserEntityResult
     */
    public function testMixedQueryNormalJoin($userEntityKey)
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', $userEntityKey ?: null);
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('sclr0', 'numPhones', Type::getType('integer'));

        // Faked result set
        $resultSet = [
            //row1
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => '2',
            ],
            [
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => '1',
            ]
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertInternalType('array', $result);
        self::assertInternalType('array', $result[0]);
        self::assertInternalType('array', $result[1]);

        // first user => 2 phonenumbers
        self::assertEquals(2, $result[0]['numPhones']);
        self::assertInstanceOf(CmsUser::class, $result[0][$userEntityKey]);

        // second user => 1 phonenumber
        self::assertEquals(1, $result[1]['numPhones']);
        self::assertInstanceOf(CmsUser::class, $result[1][$userEntityKey]);
    }

    /**
     * SELECT PARTIAL u.{id, status}, PARTIAL p.{phonenumber}, UPPER(u.name) nameUpper
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     *   JOIN u.phonenumbers p
     *
     * @dataProvider provideDataForUserEntityResult
     */
    public function testMixedQueryFetchJoin($userEntityKey)
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', $userEntityKey ?: null);
        $rsm->addJoinedEntityResult(
            CmsPhonenumber::class,
            'p',
            'u',
            'phonenumbers'
        );
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');
        $rsm->addScalarResult('sclr0', 'nameUpper', Type::getType('string'));

        // Faked result set
        $resultSet = [
            //row1
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'p__phonenumber' => '42',
                'sclr0' => 'ROMANB',
            ],
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'p__phonenumber' => '43',
                'sclr0' => 'ROMANB',
            ],
            [
                'u__id' => '2',
                'u__status' => 'developer',
                'p__phonenumber' => '91',
                'sclr0' => 'JWAGE',
            ]
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertInternalType('array', $result);
        self::assertInternalType('array', $result[0]);
        self::assertInternalType('array', $result[1]);

        self::assertInstanceOf(CmsUser::class, $result[0][$userEntityKey]);
        self::assertInstanceOf(PersistentCollection::class, $result[0][$userEntityKey]->phonenumbers);
        self::assertInstanceOf(CmsPhonenumber::class, $result[0][$userEntityKey]->phonenumbers[0]);

        self::assertInstanceOf(CmsUser::class, $result[1][$userEntityKey]);
        self::assertInstanceOf(PersistentCollection::class, $result[1][$userEntityKey]->phonenumbers);
        self::assertInstanceOf(CmsPhonenumber::class, $result[0][$userEntityKey]->phonenumbers[1]);

        // first user => 2 phonenumbers
        self::assertEquals(2, count($result[0][$userEntityKey]->phonenumbers));
        self::assertEquals('ROMANB', $result[0]['nameUpper']);

        // second user => 1 phonenumber
        self::assertEquals(1, count($result[1][$userEntityKey]->phonenumbers));
        self::assertEquals('JWAGE', $result[1]['nameUpper']);

        self::assertEquals(42, $result[0][$userEntityKey]->phonenumbers[0]->phonenumber);
        self::assertEquals(43, $result[0][$userEntityKey]->phonenumbers[1]->phonenumber);
        self::assertEquals(91, $result[1][$userEntityKey]->phonenumbers[0]->phonenumber);
    }

    /**
     * SELECT u, p, UPPER(u.name) nameUpper
     *   FROM User u
     *        INDEX BY u.id
     *   JOIN u.phonenumbers p
     *        INDEX BY p.phonenumber
     *
     * @dataProvider provideDataForUserEntityResult
     */
    public function testMixedQueryFetchJoinCustomIndex($userEntityKey)
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', $userEntityKey ?: null);
        $rsm->addJoinedEntityResult(
            CmsPhonenumber::class,
            'p',
            'u',
            'phonenumbers'
        );
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('sclr0', 'nameUpper', Type::getType('string'));
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');
        $rsm->addIndexBy('u', 'id');
        $rsm->addIndexBy('p', 'phonenumber');

        // Faked result set
        $resultSet = [
            //row1
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '42',
            ],
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '43',
            ],
            [
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => '91'
            ]
        ];


        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertInternalType('array', $result);
        self::assertInternalType('array', $result[1]);
        self::assertInternalType('array', $result[2]);

        // test the scalar values
        self::assertEquals('ROMANB', $result[1]['nameUpper']);
        self::assertEquals('JWAGE', $result[2]['nameUpper']);

        self::assertInstanceOf(CmsUser::class, $result[1][$userEntityKey]);
        self::assertInstanceOf(CmsUser::class, $result[2][$userEntityKey]);
        self::assertInstanceOf(PersistentCollection::class, $result[1][$userEntityKey]->phonenumbers);

        // first user => 2 phonenumbers. notice the custom indexing by user id
        self::assertEquals(2, count($result[1][$userEntityKey]->phonenumbers));

        // second user => 1 phonenumber. notice the custom indexing by user id
        self::assertEquals(1, count($result[2][$userEntityKey]->phonenumbers));

        // test the custom indexing of the phonenumbers
        self::assertTrue(isset($result[1][$userEntityKey]->phonenumbers['42']));
        self::assertTrue(isset($result[1][$userEntityKey]->phonenumbers['43']));
        self::assertTrue(isset($result[2][$userEntityKey]->phonenumbers['91']));
    }

    /**
     * SELECT u, p, UPPER(u.name) nameUpper, a
     *   FROM User u
     *   JOIN u.phonenumbers p
     *   JOIN u.articles a
     *
     * @dataProvider provideDataForUserEntityResult
     */
    public function testMixedQueryMultipleFetchJoin($userEntityKey)
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', $userEntityKey ?: null);
        $rsm->addJoinedEntityResult(
            CmsPhonenumber::class,
            'p',
            'u',
            'phonenumbers'
        );
        $rsm->addJoinedEntityResult(
            CmsArticle::class,
            'a',
            'u',
            'articles'
        );
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('sclr0', 'nameUpper', Type::getType('string'));
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');
        $rsm->addFieldResult('a', 'a__id', 'id');
        $rsm->addFieldResult('a', 'a__topic', 'topic');

        // Faked result set
        $resultSet = [
            //row1
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '42',
                'a__id' => '1',
                'a__topic' => 'Getting things done!'
            ],
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '43',
                'a__id' => '1',
                'a__topic' => 'Getting things done!'
            ],
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '42',
                'a__id' => '2',
                'a__topic' => 'ZendCon'
            ],
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '43',
                'a__id' => '2',
                'a__topic' => 'ZendCon'
            ],
            [
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => '91',
                'a__id' => '3',
                'a__topic' => 'LINQ'
            ],
            [
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => '91',
                'a__id' => '4',
                'a__topic' => 'PHP7'
            ],
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertTrue(is_array($result));
        self::assertTrue(is_array($result[0]));
        self::assertTrue(is_array($result[1]));

        self::assertInstanceOf(CmsUser::class, $result[0][$userEntityKey]);
        self::assertInstanceOf(PersistentCollection::class, $result[0][$userEntityKey]->phonenumbers);
        self::assertInstanceOf(CmsPhonenumber::class, $result[0][$userEntityKey]->phonenumbers[0]);
        self::assertInstanceOf(CmsPhonenumber::class, $result[0][$userEntityKey]->phonenumbers[1]);
        self::assertInstanceOf(PersistentCollection::class, $result[0][$userEntityKey]->articles);
        self::assertInstanceOf(CmsArticle::class, $result[0][$userEntityKey]->articles[0]);
        self::assertInstanceOf(CmsArticle::class, $result[0][$userEntityKey]->articles[1]);

        self::assertInstanceOf(CmsUser::class, $result[1][$userEntityKey]);
        self::assertInstanceOf(PersistentCollection::class, $result[1][$userEntityKey]->phonenumbers);
        self::assertInstanceOf(CmsPhonenumber::class, $result[1][$userEntityKey]->phonenumbers[0]);
        self::assertInstanceOf(CmsArticle::class, $result[1][$userEntityKey]->articles[0]);
        self::assertInstanceOf(CmsArticle::class, $result[1][$userEntityKey]->articles[1]);
    }

    /**
     * SELECT u, p, UPPER(u.name) nameUpper, a, c
     *   FROM User u
     *   JOIN u.phonenumbers p
     *   JOIN u.articles a
     *   LEFT JOIN a.comments c
     *
     * @dataProvider provideDataForUserEntityResult
     */
    public function testMixedQueryMultipleDeepMixedFetchJoin($userEntityKey)
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', $userEntityKey ?: null);
        $rsm->addJoinedEntityResult(
            CmsPhonenumber::class,
            'p',
            'u',
            'phonenumbers'
        );
        $rsm->addJoinedEntityResult(
            CmsArticle::class,
            'a',
            'u',
            'articles'
        );
        $rsm->addJoinedEntityResult(
            CmsComment::class,
            'c',
            'a',
            'comments'
        );
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('sclr0', 'nameUpper', Type::getType('string'));
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');
        $rsm->addFieldResult('a', 'a__id', 'id');
        $rsm->addFieldResult('a', 'a__topic', 'topic');
        $rsm->addFieldResult('c', 'c__id', 'id');
        $rsm->addFieldResult('c', 'c__topic', 'topic');

        // Faked result set
        $resultSet = [
            //row1
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '42',
                'a__id' => '1',
                'a__topic' => 'Getting things done!',
                'c__id' => '1',
                'c__topic' => 'First!'
            ],
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '43',
                'a__id' => '1',
                'a__topic' => 'Getting things done!',
                'c__id' => '1',
                'c__topic' => 'First!'
            ],
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '42',
                'a__id' => '2',
                'a__topic' => 'ZendCon',
                'c__id' => null,
                'c__topic' => null
            ],
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '43',
                'a__id' => '2',
                'a__topic' => 'ZendCon',
                'c__id' => null,
                'c__topic' => null
            ],
            [
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => '91',
                'a__id' => '3',
                'a__topic' => 'LINQ',
                'c__id' => null,
                'c__topic' => null
            ],
            [
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => '91',
                'a__id' => '4',
                'a__topic' => 'PHP7',
                'c__id' => null,
                'c__topic' => null
            ],
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertTrue(is_array($result));
        self::assertTrue(is_array($result[0]));
        self::assertTrue(is_array($result[1]));

        self::assertInstanceOf(CmsUser::class, $result[0][$userEntityKey]);
        self::assertInstanceOf(CmsUser::class, $result[1][$userEntityKey]);

        // phonenumbers
        self::assertInstanceOf(PersistentCollection::class, $result[0][$userEntityKey]->phonenumbers);
        self::assertInstanceOf(CmsPhonenumber::class, $result[0][$userEntityKey]->phonenumbers[0]);
        self::assertInstanceOf(CmsPhonenumber::class, $result[0][$userEntityKey]->phonenumbers[1]);

        self::assertInstanceOf(PersistentCollection::class, $result[1][$userEntityKey]->phonenumbers);
        self::assertInstanceOf(CmsPhonenumber::class, $result[1][$userEntityKey]->phonenumbers[0]);

        // articles
        self::assertInstanceOf(PersistentCollection::class, $result[0][$userEntityKey]->articles);
        self::assertInstanceOf(CmsArticle::class, $result[0][$userEntityKey]->articles[0]);
        self::assertInstanceOf(CmsArticle::class, $result[0][$userEntityKey]->articles[1]);

        self::assertInstanceOf(CmsArticle::class, $result[1][$userEntityKey]->articles[0]);
        self::assertInstanceOf(CmsArticle::class, $result[1][$userEntityKey]->articles[1]);

        // article comments
        self::assertInstanceOf(PersistentCollection::class, $result[0][$userEntityKey]->articles[0]->comments);
        self::assertInstanceOf(CmsComment::class, $result[0][$userEntityKey]->articles[0]->comments[0]);

        // empty comment collections
        self::assertInstanceOf(PersistentCollection::class, $result[0][$userEntityKey]->articles[1]->comments);
        self::assertEquals(0, count($result[0][$userEntityKey]->articles[1]->comments));

        self::assertInstanceOf(PersistentCollection::class, $result[1][$userEntityKey]->articles[0]->comments);
        self::assertEquals(0, count($result[1][$userEntityKey]->articles[0]->comments));
        self::assertInstanceOf(PersistentCollection::class, $result[1][$userEntityKey]->articles[1]->comments);
        self::assertEquals(0, count($result[1][$userEntityKey]->articles[1]->comments));
    }

    /**
     * Tests that the hydrator does not rely on a particular order of the rows
     * in the result set.
     *
     * DQL:
     * select c, b from Doctrine\Tests\Models\Forum\ForumCategory c inner join c.boards b
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
        $rsm->addEntityResult(ForumCategory::class, 'c');
        $rsm->addJoinedEntityResult(
            ForumBoard::class,
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
        $resultSet = [
            [
                'c__id' => '1',
                'c__position' => '0',
                'c__name' => 'First',
                'b__id' => '1',
                'b__position' => '0',
                //'b__category_id' => '1'
            ],
            [
                'c__id' => '2',
                'c__position' => '0',
                'c__name' => 'Second',
                'b__id' => '2',
                'b__position' => '0',
                //'b__category_id' => '2'
            ],
            [
                'c__id' => '1',
                'c__position' => '0',
                'c__name' => 'First',
                'b__id' => '3',
                'b__position' => '1',
                //'b__category_id' => '1'
            ],
            [
                'c__id' => '1',
                'c__position' => '0',
                'c__name' => 'First',
                'b__id' => '4',
                'b__position' => '2',
                //'b__category_id' => '1'
            ]
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertInstanceOf(ForumCategory::class, $result[0]);
        self::assertInstanceOf(ForumCategory::class, $result[1]);

        self::assertTrue($result[0] !== $result[1]);

        self::assertEquals(1, $result[0]->getId());
        self::assertEquals(2, $result[1]->getId());

        self::assertTrue(isset($result[0]->boards));
        self::assertEquals(3, count($result[0]->boards));

        self::assertTrue(isset($result[1]->boards));
        self::assertEquals(1, count($result[1]->boards));
    }

    /**
     * SELECT PARTIAL u.{id,name}
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     *
     * @group DDC-644
     */
    public function testSkipUnknownColumns()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        // Faked result set
        $resultSet = [
            [
                'u__id' => '1',
                'u__name' => 'romanb',
                'foo' => 'bar', // unknown!
            ],
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(1, count($result));
        self::assertInstanceOf(CmsUser::class, $result[0]);
    }

    /**
     * SELECT u.id, u.name
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     *
     * @dataProvider provideDataForUserEntityResult
     */
    public function testScalarQueryWithoutResultVariables($userEntityKey)
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', $userEntityKey ?: null);
        $rsm->addScalarResult('sclr0', 'id', Type::getType('integer'));
        $rsm->addScalarResult('sclr1', 'name', Type::getType('string'));

        // Faked result set
        $resultSet = [
            [
                'sclr0' => '1',
                'sclr1' => 'romanb'
            ],
            [
                'sclr0' => '2',
                'sclr1' => 'jwage'
            ]
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertInternalType('array', $result[0]);
        self::assertInternalType('array', $result[1]);

        self::assertEquals(1, $result[0]['id']);
        self::assertEquals('romanb', $result[0]['name']);

        self::assertEquals(2, $result[1]['id']);
        self::assertEquals('jwage', $result[1]['name']);
    }

    /**
     * SELECT p
     *   FROM Doctrine\Tests\Models\ECommerce\ECommerceProduct p
     */
    public function testCreatesProxyForLazyLoadingWithForeignKeys()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(ECommerceProduct::class, 'p');
        $rsm->addFieldResult('p', 'p__id', 'id');
        $rsm->addFieldResult('p', 'p__name', 'name');
        $rsm->addMetaResult('p', 'p__shipping_id', 'shipping_id', false, Type::getType('integer'));

        // Faked result set
        $resultSet = [
            [
                'p__id' => '1',
                'p__name' => 'Doctrine Book',
                'p__shipping_id' => 42
            ]
        ];

        $proxyInstance = new \Doctrine\Tests\Models\ECommerce\ECommerceShipping();

        // mocking the proxy factory
        $proxyFactory = $this->getMockBuilder(ProxyFactory::class)
                             ->setMethods(['getProxy'])
                             ->disableOriginalConstructor()
                             ->getMock();

        $proxyFactory->expects($this->once())
                     ->method('getProxy')
                     ->with($this->equalTo(ECommerceShipping::class), ['id' => 42])
                     ->will($this->returnValue($proxyInstance));

        $this->_em->setProxyFactory($proxyFactory);

        // configuring lazy loading
        $metadata = $this->_em->getClassMetadata(ECommerceProduct::class);
        $metadata->associationMappings['shipping']['fetch'] = FetchMode::LAZY;

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm);

        self::assertEquals(1, count($result));

        self::assertInstanceOf(ECommerceProduct::class, $result[0]);
    }

    /**
     * SELECT p AS product
     *   FROM Doctrine\Tests\Models\ECommerce\ECommerceProduct p
     */
    public function testCreatesProxyForLazyLoadingWithForeignKeysWithAliasedProductEntity()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(ECommerceProduct::class, 'p', 'product');
        $rsm->addFieldResult('p', 'p__id', 'id');
        $rsm->addFieldResult('p', 'p__name', 'name');
        $rsm->addMetaResult('p', 'p__shipping_id', 'shipping_id', false, Type::getType('integer'));

        // Faked result set
        $resultSet = [
            [
                'p__id' => '1',
                'p__name' => 'Doctrine Book',
                'p__shipping_id' => 42
            ]
        ];

        $proxyInstance = new \Doctrine\Tests\Models\ECommerce\ECommerceShipping();

        // mocking the proxy factory
        $proxyFactory = $this->getMockBuilder(ProxyFactory::class)
                             ->setMethods(['getProxy'])
                             ->disableOriginalConstructor()
                             ->getMock();

        $proxyFactory->expects($this->once())
                     ->method('getProxy')
                     ->with($this->equalTo(ECommerceShipping::class), ['id' => 42])
                     ->will($this->returnValue($proxyInstance));

        $this->_em->setProxyFactory($proxyFactory);

        // configuring lazy loading
        $metadata = $this->_em->getClassMetadata(ECommerceProduct::class);
        $metadata->associationMappings['shipping']['fetch'] = FetchMode::LAZY;

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm);

        self::assertEquals(1, count($result));

        self::assertInternalType('array', $result[0]);
        self::assertInstanceOf(ECommerceProduct::class, $result[0]['product']);
    }

    /**
     * SELECT PARTIAL u.{id, status}, PARTIAL a.{id, topic}, PARTIAL c.{id, topic}
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     *   LEFT JOIN u.articles a
     *   LEFT JOIN a.comments c
     */
    public function testChainedJoinWithEmptyCollections()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addJoinedEntityResult(
            CmsArticle::class,
            'a',
            'u',
            'articles'
        );
        $rsm->addJoinedEntityResult(
            CmsComment::class,
            'c',
            'a',
            'comments'
        );
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addFieldResult('a', 'a__id', 'id');
        $rsm->addFieldResult('a', 'a__topic', 'topic');
        $rsm->addFieldResult('c', 'c__id', 'id');
        $rsm->addFieldResult('c', 'c__topic', 'topic');

        // Faked result set
        $resultSet = [
            //row1
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'a__id' => null,
                'a__topic' => null,
                'c__id' => null,
                'c__topic' => null
            ],
            [
                'u__id' => '2',
                'u__status' => 'developer',
                'a__id' => null,
                'a__topic' => null,
                'c__id' => null,
                'c__topic' => null
            ],
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertInstanceOf(CmsUser::class, $result[0]);
        self::assertInstanceOf(CmsUser::class, $result[1]);

        self::assertEquals(0, $result[0]->articles->count());
        self::assertEquals(0, $result[1]->articles->count());
    }

    /**
     * SELECT PARTIAL u.{id, status} AS user, PARTIAL a.{id, topic}, PARTIAL c.{id, topic}
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     *   LEFT JOIN u.articles a
     *   LEFT JOIN a.comments c
     */
    public function testChainedJoinWithEmptyCollectionsWithAliasedUserEntity()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', 'user');
        $rsm->addJoinedEntityResult(
            CmsArticle::class,
            'a',
            'u',
            'articles'
        );
        $rsm->addJoinedEntityResult(
            CmsComment::class,
            'c',
            'a',
            'comments'
        );
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addFieldResult('a', 'a__id', 'id');
        $rsm->addFieldResult('a', 'a__topic', 'topic');
        $rsm->addFieldResult('c', 'c__id', 'id');
        $rsm->addFieldResult('c', 'c__topic', 'topic');

        // Faked result set
        $resultSet = [
            //row1
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'a__id' => null,
                'a__topic' => null,
                'c__id' => null,
                'c__topic' => null
            ],
            [
                'u__id' => '2',
                'u__status' => 'developer',
                'a__id' => null,
                'a__topic' => null,
                'c__id' => null,
                'c__topic' => null
            ],
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertInternalType('array', $result[0]);
        self::assertInstanceOf(CmsUser::class, $result[0]['user']);

        self::assertInternalType('array', $result[1]);
        self::assertInstanceOf(CmsUser::class, $result[1]['user']);

        self::assertEquals(0, $result[0]['user']->articles->count());
        self::assertEquals(0, $result[1]['user']->articles->count());
    }

    /**
     * SELECT PARTIAL u.{id,status}, a.id, a.topic, c.id as cid, c.topic as ctopic
     *   FROM CmsUser u
     *   LEFT JOIN u.articles a
     *   LEFT JOIN a.comments c
     *
     * @todo Figure it out why this test is commented out and provide a better description in docblock
     *
     * @group bubu
     * @dataProvider provideDataForUserEntityResult
     */
    /*public function testChainedJoinWithScalars($userEntityKey)
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u', $userEntityKey ?: null);
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('a__id', 'id', Type::getType('integer'));
        $rsm->addScalarResult('a__topic', 'topic', Type::getType('string'));
        $rsm->addScalarResult('c__id', 'cid', Type::getType('integer'));
        $rsm->addScalarResult('c__topic', 'ctopic', Type::getType('string'));

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

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, array(Query::HINT_FORCE_PARTIAL_LOAD => true));

        \Doctrine\Common\Util\Debug::dump($result, 3);

        self::assertEquals(1, count($result));

        self::assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $result[0][$userEntityKey]); // User object
        self::assertEquals(42, $result[0]['id']);
        self::assertEquals('The First', $result[0]['topic']);
        self::assertEquals(1, $result[0]['cid']);
        self::assertEquals('First Comment', $result[0]['ctopic']);
    }*/

    /**
     * SELECT PARTIAL u.{id, name}
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     */
    public function testResultIteration()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        // Faked result set
        $resultSet = [
            [
                'u__id' => '1',
                'u__name' => 'romanb'
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage'
            ]
        ];

        $stmt           = new HydratorMockStatement($resultSet);
        $hydrator       = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $iterableResult = $hydrator->iterate($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);
        $rowNum         = 0;

        while (($row = $iterableResult->next()) !== false) {
            self::assertEquals(1, count($row));
            self::assertInstanceOf(CmsUser::class, $row[0]);

            if ($rowNum == 0) {
                self::assertEquals(1, $row[0]->id);
                self::assertEquals('romanb', $row[0]->name);
            } else if ($rowNum == 1) {
                self::assertEquals(2, $row[0]->id);
                self::assertEquals('jwage', $row[0]->name);
            }

            ++$rowNum;
        }
    }

    /**
     * SELECT PARTIAL u.{id, name}
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     */
    public function testResultIterationWithAliasedUserEntity()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', 'user');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');

        // Faked result set
        $resultSet = [
            [
                'u__id' => '1',
                'u__name' => 'romanb'
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage'
            ]
        ];

        $stmt           = new HydratorMockStatement($resultSet);
        $hydrator       = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $iterableResult = $hydrator->iterate($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);
        $rowNum         = 0;

        while (($row = $iterableResult->next()) !== false) {
            self::assertEquals(1, count($row));
            self::assertArrayHasKey(0, $row);
            self::assertArrayHasKey('user', $row[0]);
            self::assertInstanceOf(CmsUser::class, $row[0]['user']);

            if ($rowNum == 0) {
                self::assertEquals(1, $row[0]['user']->id);
                self::assertEquals('romanb', $row[0]['user']->name);
            } else if ($rowNum == 1) {
                self::assertEquals(2, $row[0]['user']->id);
                self::assertEquals('jwage', $row[0]['user']->name);
            }

            ++$rowNum;
        }
    }

    /**
     * Checks if multiple joined multiple-valued collections is hydrated correctly.
     *
     * SELECT PARTIAL u.{id, status}, PARTIAL g.{id, name}, PARTIAL p.{phonenumber}
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     *
     * @group DDC-809
     */
    public function testManyToManyHydration()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');
        $rsm->addJoinedEntityResult(CmsGroup::class, 'g', 'u', 'groups');
        $rsm->addFieldResult('g', 'g__id', 'id');
        $rsm->addFieldResult('g', 'g__name', 'name');
        $rsm->addJoinedEntityResult(CmsPhonenumber::class, 'p', 'u', 'phonenumbers');
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');

        // Faked result set
        $resultSet = [
            [
                'u__id' => '1',
                'u__name' => 'romanb',
                'g__id' => '3',
                'g__name' => 'TestGroupB',
                'p__phonenumber' => 1111,
            ],
            [
                'u__id' => '1',
                'u__name' => 'romanb',
                'g__id' => '5',
                'g__name' => 'TestGroupD',
                'p__phonenumber' => 1111,
            ],
            [
                'u__id' => '1',
                'u__name' => 'romanb',
                'g__id' => '3',
                'g__name' => 'TestGroupB',
                'p__phonenumber' => 2222,
            ],
            [
                'u__id' => '1',
                'u__name' => 'romanb',
                'g__id' => '5',
                'g__name' => 'TestGroupD',
                'p__phonenumber' => 2222,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '2',
                'g__name' => 'TestGroupA',
                'p__phonenumber' => 3333,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '3',
                'g__name' => 'TestGroupB',
                'p__phonenumber' => 3333,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '4',
                'g__name' => 'TestGroupC',
                'p__phonenumber' => 3333,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '5',
                'g__name' => 'TestGroupD',
                'p__phonenumber' => 3333,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '2',
                'g__name' => 'TestGroupA',
                'p__phonenumber' => 4444,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '3',
                'g__name' => 'TestGroupB',
                'p__phonenumber' => 4444,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '4',
                'g__name' => 'TestGroupC',
                'p__phonenumber' => 4444,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '5',
                'g__name' => 'TestGroupD',
                'p__phonenumber' => 4444,
            ],
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertContainsOnly(CmsUser::class, $result);

        self::assertEquals(2, count($result[0]->groups));
        self::assertEquals(2, count($result[0]->phonenumbers));

        self::assertEquals(4, count($result[1]->groups));
        self::assertEquals(2, count($result[1]->phonenumbers));
    }

    /**
     * Checks if multiple joined multiple-valued collections is hydrated correctly.
     *
     * SELECT PARTIAL u.{id, status} As user, PARTIAL g.{id, name}, PARTIAL p.{phonenumber}
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     *
     * @group DDC-809
     */
    public function testManyToManyHydrationWithAliasedUserEntity()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', 'user');
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__name', 'name');
        $rsm->addJoinedEntityResult(CmsGroup::class, 'g', 'u', 'groups');
        $rsm->addFieldResult('g', 'g__id', 'id');
        $rsm->addFieldResult('g', 'g__name', 'name');
        $rsm->addJoinedEntityResult(CmsPhonenumber::class, 'p', 'u', 'phonenumbers');
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');

        // Faked result set
        $resultSet = [
            [
                'u__id' => '1',
                'u__name' => 'romanb',
                'g__id' => '3',
                'g__name' => 'TestGroupB',
                'p__phonenumber' => 1111,
            ],
            [
                'u__id' => '1',
                'u__name' => 'romanb',
                'g__id' => '5',
                'g__name' => 'TestGroupD',
                'p__phonenumber' => 1111,
            ],
            [
                'u__id' => '1',
                'u__name' => 'romanb',
                'g__id' => '3',
                'g__name' => 'TestGroupB',
                'p__phonenumber' => 2222,
            ],
            [
                'u__id' => '1',
                'u__name' => 'romanb',
                'g__id' => '5',
                'g__name' => 'TestGroupD',
                'p__phonenumber' => 2222,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '2',
                'g__name' => 'TestGroupA',
                'p__phonenumber' => 3333,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '3',
                'g__name' => 'TestGroupB',
                'p__phonenumber' => 3333,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '4',
                'g__name' => 'TestGroupC',
                'p__phonenumber' => 3333,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '5',
                'g__name' => 'TestGroupD',
                'p__phonenumber' => 3333,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '2',
                'g__name' => 'TestGroupA',
                'p__phonenumber' => 4444,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '3',
                'g__name' => 'TestGroupB',
                'p__phonenumber' => 4444,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '4',
                'g__name' => 'TestGroupC',
                'p__phonenumber' => 4444,
            ],
            [
                'u__id' => '2',
                'u__name' => 'jwage',
                'g__id' => '5',
                'g__name' => 'TestGroupD',
                'p__phonenumber' => 4444,
            ],
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertInternalType('array', $result[0]);
        self::assertInstanceOf(CmsUser::class, $result[0]['user']);
        self::assertInternalType('array', $result[1]);
        self::assertInstanceOf(CmsUser::class, $result[1]['user']);

        self::assertEquals(2, count($result[0]['user']->groups));
        self::assertEquals(2, count($result[0]['user']->phonenumbers));

        self::assertEquals(4, count($result[1]['user']->groups));
        self::assertEquals(2, count($result[1]['user']->phonenumbers));
    }

    /**
     * SELECT PARTIAL u.{id, status}, UPPER(u.name) as nameUpper
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     *
     * @group DDC-1358
     * @dataProvider provideDataForUserEntityResult
     */
    public function testMissingIdForRootEntity($userEntityKey)
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', $userEntityKey ?: null);
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('sclr0', 'nameUpper', Type::getType('string'));

        // Faked result set
        $resultSet = [
            //row1
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
            ],
            [
                'u__id' => null,
                'u__status' => null,
                'sclr0' => 'ROMANB',
            ],
            [
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
            ],
            [
                'u__id' => null,
                'u__status' => null,
                'sclr0' => 'JWAGE',
            ],
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(4, count($result), "Should hydrate four results.");

        self::assertEquals('ROMANB', $result[0]['nameUpper']);
        self::assertEquals('ROMANB', $result[1]['nameUpper']);
        self::assertEquals('JWAGE', $result[2]['nameUpper']);
        self::assertEquals('JWAGE', $result[3]['nameUpper']);

        self::assertInstanceOf(CmsUser::class, $result[0][$userEntityKey]);
        self::assertNull($result[1][$userEntityKey]);

        self::assertInstanceOf(CmsUser::class, $result[2][$userEntityKey]);
        self::assertNull($result[3][$userEntityKey]);
    }

    /**
     * SELECT PARTIAL u.{id, status}, PARTIAL p.{phonenumber}, UPPER(u.name) AS nameUpper
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     *   LEFT JOIN u.phonenumbers u
     *
     * @group DDC-1358
     * @dataProvider provideDataForUserEntityResult
     */
    public function testMissingIdForCollectionValuedChildEntity($userEntityKey)
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', $userEntityKey ?: null);
        $rsm->addJoinedEntityResult(
            CmsPhonenumber::class,
            'p',
            'u',
            'phonenumbers'
        );
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('sclr0', 'nameUpper', Type::getType('string'));
        $rsm->addFieldResult('p', 'p__phonenumber', 'phonenumber');

        // Faked result set
        $resultSet = [
            //row1
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => '42',
            ],
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'p__phonenumber' => null
            ],
            [
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => '91'
            ],
            [
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
                'p__phonenumber' => null
            ]
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertEquals(1, $result[0][$userEntityKey]->phonenumbers->count());
        self::assertEquals(1, $result[1][$userEntityKey]->phonenumbers->count());
    }

    /**
     * SELECT PARTIAL u.{id, status}, PARTIAL a.{id, city}, UPPER(u.name) AS nameUpper
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     *   JOIN u.address a
     *
     * @group DDC-1358
     * @dataProvider provideDataForUserEntityResult
     */
    public function testMissingIdForSingleValuedChildEntity($userEntityKey)
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', $userEntityKey ?: null);
        $rsm->addJoinedEntityResult(
            CmsAddress::class,
            'a',
            'u',
            'address'
        );
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('sclr0', 'nameUpper', Type::getType('string'));
        $rsm->addFieldResult('a', 'a__id', 'id');
        $rsm->addFieldResult('a', 'a__city', 'city');
        $rsm->addMetaResult('a', 'user_id', 'user_id', false, Type::getType('string'));

        // Faked result set
        $resultSet = [
            //row1
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
                'a__id' => 1,
                'a__city' => 'Berlin',
            ],
            [
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'BENJAMIN',
                'a__id' => null,
                'a__city' => null,
            ],
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertInstanceOf(CmsAddress::class, $result[0][$userEntityKey]->address);
        self::assertNull($result[1][$userEntityKey]->address);
    }

    /**
     * SELECT PARTIAL u.{id, status}, UPPER(u.name) AS nameUpper
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     *        INDEX BY u.id
     *
     * @group DDC-1385
     * @dataProvider provideDataForUserEntityResult
     */
    public function testIndexByAndMixedResult($userEntityKey)
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', $userEntityKey ?: null);
        $rsm->addFieldResult('u', 'u__id', 'id');
        $rsm->addFieldResult('u', 'u__status', 'status');
        $rsm->addScalarResult('sclr0', 'nameUpper', Type::getType('string'));
        $rsm->addIndexBy('u', 'id');

        // Faked result set
        $resultSet = [
            //row1
            [
                'u__id' => '1',
                'u__status' => 'developer',
                'sclr0' => 'ROMANB',
            ],
            [
                'u__id' => '2',
                'u__status' => 'developer',
                'sclr0' => 'JWAGE',
            ],
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(2, count($result));

        self::assertTrue(isset($result[1]));
        self::assertEquals(1, $result[1][$userEntityKey]->id);

        self::assertTrue(isset($result[2]));
        self::assertEquals(2, $result[2][$userEntityKey]->id);
    }

    /**
     * SELECT UPPER(u.name) AS nameUpper
     *   FROM Doctrine\Tests\Models\CMS\CmsUser u
     *
     * @group DDC-1385
     * @dataProvider provideDataForUserEntityResult
     */
    public function testIndexByScalarsOnly($userEntityKey)
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(CmsUser::class, 'u', $userEntityKey ?: null);
        $rsm->addScalarResult('sclr0', 'nameUpper', Type::getType('string'));
        $rsm->addIndexByScalar('sclr0');

        // Faked result set
        $resultSet = [
            //row1
            [
                'sclr0' => 'ROMANB',
            ],
            [
                'sclr0' => 'JWAGE',
            ],
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertEquals(
            [
                'ROMANB' => ['nameUpper' => 'ROMANB'],
                'JWAGE'  => ['nameUpper' => 'JWAGE']
            ],
            $result
        );
    }


    /**
     * @group DDC-1470
     *
     * @expectedException \Doctrine\ORM\Internal\Hydration\HydrationException
     * @expectedExceptionMessage The meta mapping for the discriminator column "c_discr" is missing for "Doctrine\Tests\Models\Company\CompanyFixContract" using the DQL alias "c".
     */
    public function testMissingMetaMappingException()
    {
        $rsm = new ResultSetMapping;

        $rsm->addEntityResult(CompanyFixContract::class, 'c');
        $rsm->addJoinedEntityResult(CompanyEmployee::class, 'e', 'c', 'salesPerson');
        $rsm->addFieldResult('c', 'c__id', 'id');
        $rsm->setDiscriminatorColumn('c', 'c_discr');

        $resultSet = [
              [
                  'c__id'   => '1',
                  'c_discr' => 'fix',
              ],
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $hydrator->hydrateAll($stmt, $rsm);
    }

    /**
     * @group DDC-1470
     *
     * @expectedException \Doctrine\ORM\Internal\Hydration\HydrationException
     * @expectedExceptionMessage The discriminator column "discr" is missing for "Doctrine\Tests\Models\Company\CompanyEmployee" using the DQL alias "e".
     */
    public function testMissingDiscriminatorColumnException()
    {
        $rsm = new ResultSetMapping;

        $rsm->addEntityResult(CompanyFixContract::class, 'c');
        $rsm->addJoinedEntityResult(CompanyEmployee::class, 'e', 'c', 'salesPerson');
        $rsm->addFieldResult('c', 'c__id', 'id');
        $rsm->addMetaResult('c', 'c_discr', 'discr', false, Type::getType('string'));
        $rsm->setDiscriminatorColumn('c', 'c_discr');
        $rsm->addFieldResult('e', 'e__id', 'id');
        $rsm->addFieldResult('e', 'e__name', 'name');
        $rsm->addMetaResult('e ', 'e_discr', 'discr', false, Type::getType('string'));
        $rsm->setDiscriminatorColumn('e', 'e_discr');

        $resultSet = [
              [
                  'c__id'   => '1',
                  'c_discr' => 'fix',
                  'e__id'   => '1',
                  'e__name' => 'Fabio B. Silva'
              ],
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $hydrator->hydrateAll($stmt, $rsm);
    }

    /**
     * @group DDC-3076
     *
     * @expectedException \Doctrine\ORM\Internal\Hydration\HydrationException
     * @expectedExceptionMessage The discriminator value "subworker" is invalid. It must be one of "person", "manager", "employee".
     */
    public function testInvalidDiscriminatorValueException()
    {
        $rsm = new ResultSetMapping;

        $rsm->addEntityResult(CompanyPerson::class, 'p');
        $rsm->addFieldResult('p', 'p__id', 'id');
        $rsm->addFieldResult('p', 'p__name', 'name');
        $rsm->addMetaResult('p', 'discr', 'discr', false, Type::getType('string'));
        $rsm->setDiscriminatorColumn('p', 'discr');

        $resultSet = [
              [
                  'p__id'   => '1',
                  'p__name' => 'Fabio B. Silva',
                  'discr'   => 'subworker'
              ],
        ];

        $stmt       = new HydratorMockStatement($resultSet);
        $hydrator   = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $hydrator->hydrateAll($stmt, $rsm);
    }

    public function testFetchJoinCollectionValuedAssociationWithDefaultArrayValue()
    {
        $rsm = new ResultSetMapping;

        $rsm->addEntityResult(EntityWithArrayDefaultArrayValueM2M::class, 'e1', null);
        $rsm->addJoinedEntityResult(SimpleEntity::class, 'e2', 'e1', 'collection');
        $rsm->addFieldResult('e1', 'a1__id', 'id');
        $rsm->addFieldResult('e2', 'e2__id', 'id');

        $resultSet = [
            [
                'a1__id' => '1',
                'e2__id' => '1',
            ]
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->_em);
        $result   = $hydrator->hydrateAll($stmt, $rsm);

        self::assertCount(1, $result);
        self::assertInstanceOf(EntityWithArrayDefaultArrayValueM2M::class, $result[0]);
        self::assertInstanceOf(PersistentCollection::class, $result[0]->collection);
        self::assertCount(1, $result[0]->collection);
        self::assertInstanceOf(SimpleEntity::class, $result[0]->collection[0]);
    }
}
