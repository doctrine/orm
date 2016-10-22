<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping\FetchMode;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\UnexpectedResultException;
use Doctrine\Tests\Models\CMS\CmsUser,
    Doctrine\Tests\Models\CMS\CmsArticle,
    Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Functional Query tests.
 *
 * @author robo
 */
class QueryTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testSimpleQueries()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery("select u, upper(u.name) from Doctrine\Tests\Models\CMS\CmsUser u where u.username = 'gblanco'");

        $result = $query->getResult();

        self::assertEquals(1, count($result));
        self::assertInstanceOf(CmsUser::class, $result[0][0]);
        self::assertEquals('Guilherme', $result[0][0]->name);
        self::assertEquals('gblanco', $result[0][0]->username);
        self::assertEquals('developer', $result[0][0]->status);
        self::assertEquals('GUILHERME', $result[0][1]);

        $resultArray = $query->getArrayResult();
        self::assertEquals(1, count($resultArray));
        self::assertTrue(is_array($resultArray[0][0]));
        self::assertEquals('Guilherme', $resultArray[0][0]['name']);
        self::assertEquals('gblanco', $resultArray[0][0]['username']);
        self::assertEquals('developer', $resultArray[0][0]['status']);
        self::assertEquals('GUILHERME', $resultArray[0][1]);

        $scalarResult = $query->getScalarResult();
        self::assertEquals(1, count($scalarResult));
        self::assertEquals('Guilherme', $scalarResult[0]['u_name']);
        self::assertEquals('gblanco', $scalarResult[0]['u_username']);
        self::assertEquals('developer', $scalarResult[0]['u_status']);
        self::assertEquals('GUILHERME', $scalarResult[0][1]);

        $query = $this->_em->createQuery("select upper(u.name) from Doctrine\Tests\Models\CMS\CmsUser u where u.username = 'gblanco'");
        self::assertEquals('GUILHERME', $query->getSingleScalarResult());
    }

    public function testJoinQueries()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $article1 = new CmsArticle;
        $article1->topic = "Doctrine 2";
        $article1->text = "This is an introduction to Doctrine 2.";
        $user->addArticle($article1);

        $article2 = new CmsArticle;
        $article2->topic = "Symfony 2";
        $article2->text = "This is an introduction to Symfony 2.";
        $user->addArticle($article2);

        $this->_em->persist($user);
        $this->_em->persist($article1);
        $this->_em->persist($article2);

        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery('select u, a from ' . CmsUser::class . ' u join u.articles a ORDER BY a.topic');
        $users = $query->getResult();
        self::assertEquals(1, count($users));
        self::assertInstanceOf(CmsUser::class, $users[0]);
        self::assertEquals(2, count($users[0]->articles));
        self::assertEquals('Doctrine 2', $users[0]->articles[0]->topic);
        self::assertEquals('Symfony 2', $users[0]->articles[1]->topic);
    }

    public function testUsingZeroBasedQueryParameterShouldWork()
    {
        $user = new CmsUser;
        $user->name = 'Jonathan';
        $user->username = 'jwage';
        $user->status = 'developer';
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $q = $this->_em->createQuery('SELECT u FROM ' . CmsUser::class . ' u WHERE u.username = ?0');
        $q->setParameter(0, 'jwage');
        $user = $q->getSingleResult();

        self::assertNotNull($user);
    }

    public function testUsingUnknownQueryParameterShouldThrowException()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Invalid parameter: token 2 is not defined in the query.');

        $q = $this->_em->createQuery('SELECT u FROM ' . CmsUser::class . ' u WHERE u.name = ?1');
        $q->setParameter(2, 'jwage');
        $user = $q->getSingleResult();
    }

    public function testTooManyParametersShouldThrowException()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Too many parameters: the query defines 1 parameters and you bound 2');

        $q = $this->_em->createQuery('SELECT u FROM ' . CmsUser::class . ' u WHERE u.name = ?1');
        $q->setParameter(1, 'jwage');
        $q->setParameter(2, 'jwage');

        $user = $q->getSingleResult();
    }

    public function testTooFewParametersShouldThrowException()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Too few parameters: the query defines 1 parameters but you only bound 0');

        $this->_em->createQuery('SELECT u FROM ' . CmsUser::class . ' u WHERE u.name = ?1')
                  ->getSingleResult();
    }

    public function testInvalidInputParameterThrowsException()
    {
        $this->expectException(QueryException::class);

        $this->_em->createQuery('SELECT u FROM ' . CmsUser::class . ' u WHERE u.name = ?')
                  ->setParameter(1, 'jwage')
                  ->getSingleResult();
    }

    public function testSetParameters()
    {
        $parameters = new ArrayCollection();
        $parameters->add(new Parameter(1, 'jwage'));
        $parameters->add(new Parameter(2, 'active'));

        $this->_em->createQuery('SELECT u FROM ' . CmsUser::class . ' u WHERE u.name = ?1 AND u.status = ?2')
                  ->setParameters($parameters)
                  ->getResult();

        $extractValue = function (Parameter $parameter) {
            return $parameter->getValue();
        };

        self::assertSame(
            $parameters->map($extractValue)->toArray(),
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery]['params']
        );
    }

    public function testSetParametersBackwardsCompatible()
    {
        $parameters = [1 => 'jwage', 2 => 'active'];

        $this->_em->createQuery('SELECT u FROM ' . CmsUser::class . ' u WHERE u.name = ?1 AND u.status = ?2')
                  ->setParameters($parameters)
                  ->getResult();

        self::assertSame(
            array_values($parameters),
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery]['params']
        );
    }

    /**
     * @group DDC-1070
     */
    public function testIterateResultAsArrayAndParams()
    {
        $article1 = new CmsArticle;
        $article1->topic = "Doctrine 2";
        $article1->text = "This is an introduction to Doctrine 2.";

        $article2 = new CmsArticle;
        $article2->topic = "Symfony 2";
        $article2->text = "This is an introduction to Symfony 2.";

        $this->_em->persist($article1);
        $this->_em->persist($article2);

        $this->_em->flush();
        $this->_em->clear();
        $articleId = $article1->id;

        $query = $this->_em->createQuery('select a from ' . CmsArticle::class . ' a WHERE a.topic = ?1');
        $articles = $query->iterate(new ArrayCollection([new Parameter(1, 'Doctrine 2')]), Query::HYDRATE_ARRAY);

        $found = [];

        foreach ($articles AS $article) {
            $found[] = $article;
        }

        self::assertEquals(1, count($found));
        self::assertEquals(
            [
                [
                    [
                        'id'      => $articleId,
                        'topic'   => 'Doctrine 2',
                        'text'    => 'This is an introduction to Doctrine 2.',
                        'version' => 1
                    ]
                ]
            ],
            $found
        );
    }

    public function testIterateResult_IterativelyBuildUpUnitOfWork()
    {
        $article1 = new CmsArticle;
        $article1->topic = "Doctrine 2";
        $article1->text = "This is an introduction to Doctrine 2.";

        $article2 = new CmsArticle;
        $article2->topic = "Symfony 2";
        $article2->text = "This is an introduction to Symfony 2.";

        $this->_em->persist($article1);
        $this->_em->persist($article2);

        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery('select a from ' . CmsArticle::class . ' a');
        $articles = $query->iterate();

        $iteratedCount = 0;
        $topics = [];

        foreach($articles AS $row) {
            $article = $row[0];
            $topics[] = $article->topic;

            $identityMap = $this->_em->getUnitOfWork()->getIdentityMap();
            $identityMapCount = count($identityMap[CmsArticle::class]);
            self::assertTrue($identityMapCount>$iteratedCount);

            $iteratedCount++;
        }

        self::assertSame(["Doctrine 2", "Symfony 2"], $topics);
        self::assertSame(2, $iteratedCount);

        $this->_em->flush();
        $this->_em->clear();
    }

    public function testIterateResultClearEveryCycle()
    {
        $article1 = new CmsArticle;
        $article1->topic = "Doctrine 2";
        $article1->text = "This is an introduction to Doctrine 2.";

        $article2 = new CmsArticle;
        $article2->topic = "Symfony 2";
        $article2->text = "This is an introduction to Symfony 2.";

        $this->_em->persist($article1);
        $this->_em->persist($article2);

        $this->_em->flush();
        $this->_em->clear();

        $query    = $this->_em->createQuery("select a from Doctrine\Tests\Models\CMS\CmsArticle a");
        $articles = $query->iterate();

        $iteratedCount = 0;
        $topics = [];
        foreach($articles AS $row) {
            $article  = $row[0];
            $topics[] = $article->topic;

            $this->_em->clear();

            $iteratedCount++;
        }

        self::assertSame(["Doctrine 2", "Symfony 2"], $topics);
        self::assertSame(2, $iteratedCount);

        $this->_em->flush();
    }

    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     */
    public function testIterateResult_FetchJoinedCollection_ThrowsException()
    {
        $query = $this->_em->createQuery("SELECT u, a FROM ' . CmsUser::class . ' u JOIN u.articles a");
        $articles = $query->iterate();
    }

    /**
     * @expectedException Doctrine\ORM\NoResultException
     */
    public function testGetSingleResultThrowsExceptionOnNoResult()
    {
        $this->_em->createQuery("select a from Doctrine\Tests\Models\CMS\CmsArticle a")
             ->getSingleResult();
    }

    /**
     * @expectedException Doctrine\ORM\NoResultException
     */
    public function testGetSingleScalarResultThrowsExceptionOnNoResult()
    {
        $this->_em->createQuery("select a from Doctrine\Tests\Models\CMS\CmsArticle a")
             ->getSingleScalarResult();
    }

    /**
     * @expectedException Doctrine\ORM\NonUniqueResultException
     */
    public function testGetSingleScalarResultThrowsExceptionOnNonUniqueResult()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $article1 = new CmsArticle;
        $article1->topic = "Doctrine 2";
        $article1->text = "This is an introduction to Doctrine 2.";
        $user->addArticle($article1);

        $article2 = new CmsArticle;
        $article2->topic = "Symfony 2";
        $article2->text = "This is an introduction to Symfony 2.";
        $user->addArticle($article2);

        $this->_em->persist($user);
        $this->_em->persist($article1);
        $this->_em->persist($article2);

        $this->_em->flush();
        $this->_em->clear();

        $this->_em->createQuery("select a from Doctrine\Tests\Models\CMS\CmsArticle a")
             ->getSingleScalarResult();
    }

    public function testModifiedLimitQuery()
    {
        for ($i = 0; $i < 5; $i++) {
            $user = new CmsUser;
            $user->name = 'Guilherme' . $i;
            $user->username = 'gblanco' . $i;
            $user->status = 'developer';
            $this->_em->persist($user);
        }

        $this->_em->flush();
        $this->_em->clear();

        $data = $this->_em->createQuery('SELECT u FROM ' . CmsUser::class . ' u')
                  ->setFirstResult(1)
                  ->setMaxResults(2)
                  ->getResult();

        self::assertEquals(2, count($data));
        self::assertEquals('gblanco1', $data[0]->username);
        self::assertEquals('gblanco2', $data[1]->username);

        $data = $this->_em->createQuery('SELECT u FROM ' . CmsUser::class . ' u')
                  ->setFirstResult(3)
                  ->setMaxResults(2)
                  ->getResult();

        self::assertEquals(2, count($data));
        self::assertEquals('gblanco3', $data[0]->username);
        self::assertEquals('gblanco4', $data[1]->username);

        $data = $this->_em->createQuery('SELECT u FROM ' . CmsUser::class . ' u')
                  ->setFirstResult(3)
                  ->setMaxResults(2)
                  ->getScalarResult();
    }

    public function testSupportsQueriesWithEntityNamespaces()
    {
        $this->_em->getConfiguration()->addEntityNamespace('CMS', 'Doctrine\Tests\Models\CMS');

        try {
            $query = $this->_em->createQuery('UPDATE CMS:CmsUser u SET u.name = ?1');

            self::assertEquals('UPDATE "cms_users" SET "name" = ?', $query->getSQL());

            $query->free();
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->_em->getConfiguration()->setEntityNamespaces([]);
    }

    /**
     * @group DDC-604
     */
    public function testEntityParameters()
    {
        $article = new CmsArticle;
        $article->topic = "dr. dolittle";
        $article->text = "Once upon a time ...";
        $author = new CmsUser;
        $author->name = "anonymous";
        $author->username = "anon";
        $author->status = "here";
        $article->user = $author;
        $this->_em->persist($author);
        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->clear();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $q = $this->_em->createQuery("select a from Doctrine\Tests\Models\CMS\CmsArticle a where a.topic = :topic and a.user = :user")
                ->setParameter("user", $this->_em->getReference(CmsUser::class, $author->id))
                ->setParameter("topic", "dr. dolittle");

        $result = $q->getResult();
        self::assertEquals(1, count($result));
        self::assertInstanceOf(CmsArticle::class, $result[0]);
        self::assertEquals("dr. dolittle", $result[0]->topic);
        self::assertInstanceOf(Proxy::class, $result[0]->user);
        self::assertFalse($result[0]->user->__isInitialized__);
    }

    /**
     * @group DDC-952
     */
    public function testEnableFetchEagerMode()
    {
        for ($i = 0; $i < 10; $i++) {
            $article = new CmsArticle;

            $article->topic = "dr. dolittle";
            $article->text = "Once upon a time ...";

            $author = new CmsUser;

            $author->name = "anonymous";
            $author->username = "anon".$i;
            $author->status = "here";
            $article->user = $author;

            $this->_em->persist($author);
            $this->_em->persist($article);
        }

        $this->_em->flush();
        $this->_em->clear();

        $articles = $this->_em
            ->createQuery('select a from Doctrine\Tests\Models\CMS\CmsArticle a')
            ->setFetchMode(CmsArticle::class, 'user', FetchMode::EAGER)
            ->getResult();

        self::assertEquals(10, count($articles));

        foreach ($articles AS $article) {
            self::assertNotInstanceOf(Proxy::class, $article);
        }
    }

    /**
     * @group DDC-991
     */
    public function testgetOneOrNullResult()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery("select u from " . CmsUser::class . " u where u.username = 'gblanco'");

        $fetchedUser = $query->getOneOrNullResult();
        self::assertInstanceOf(CmsUser::class, $fetchedUser);
        self::assertEquals('gblanco', $fetchedUser->username);

        $query = $this->_em->createQuery("select u.username from " . CmsUser::class . " u where u.username = 'gblanco'");
        $fetchedUsername = $query->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR);
        self::assertEquals('gblanco', $fetchedUsername);
    }

    /**
     * @group DDC-991
     */
    public function testgetOneOrNullResultSeveralRows()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';
        $this->_em->persist($user);
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'developer';
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u");

        $this->expectException(NonUniqueResultException::class);

        $fetchedUser = $query->getOneOrNullResult();
    }

    /**
     * @group DDC-991
     */
    public function testgetOneOrNullResultNoRows()
    {
        $query = $this->_em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u");
        self::assertNull($query->getOneOrNullResult());

        $query = $this->_em->createQuery("select u.username from Doctrine\Tests\Models\CMS\CmsUser u where u.username = 'gblanco'");
        self::assertNull($query->getOneOrNullResult(Query::HYDRATE_SCALAR));
    }

    /**
     * @group DBAL-171
     */
    public function testParameterOrder()
    {
        $user1 = new CmsUser;
        $user1->name = 'Benjamin';
        $user1->username = 'beberlei';
        $user1->status = 'developer';
        $this->_em->persist($user1);

        $user2 = new CmsUser;
        $user2->name = 'Roman';
        $user2->username = 'romanb';
        $user2->status = 'developer';
        $this->_em->persist($user2);

        $user3 = new CmsUser;
        $user3->name = 'Jonathan';
        $user3->username = 'jwage';
        $user3->status = 'developer';
        $this->_em->persist($user3);

        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.status = :a AND u.id IN (:b)");
        $query->setParameters(new ArrayCollection(
            [
            new Parameter('b', [$user1->id, $user2->id, $user3->id]),
            new Parameter('a', 'developer')
            ]
        ));
        $result = $query->getResult();

        self::assertEquals(3, count($result));
    }

    public function testDqlWithAutoInferOfParameters()
    {
        $user = new CmsUser;
        $user->name = 'Benjamin';
        $user->username = 'beberlei';
        $user->status = 'developer';
        $this->_em->persist($user);

        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'developer';
        $this->_em->persist($user);

        $user = new CmsUser;
        $user->name = 'Jonathan';
        $user->username = 'jwage';
        $user->status = 'developer';
        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username IN (?0)");
        $query->setParameter(0, ['beberlei', 'jwage']);

        $users = $query->execute();

        self::assertEquals(2, count($users));
    }

    public function testQueryBuilderWithStringWhereClauseContainingOrAndConditionalPrimary()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from(CmsUser::class, 'u')
           ->innerJoin('u.articles', 'a')
           ->where('(u.id = 0) OR (u.id IS NULL)');

        $query = $qb->getQuery();
        $users = $query->execute();

        self::assertEquals(0, count($users));
    }

    public function testQueryWithArrayOfEntitiesAsParameter()
    {
        $userA = new CmsUser;
        $userA->name = 'Benjamin';
        $userA->username = 'beberlei';
        $userA->status = 'developer';
        $this->_em->persist($userA);

        $userB = new CmsUser;
        $userB->name = 'Roman';
        $userB->username = 'romanb';
        $userB->status = 'developer';
        $this->_em->persist($userB);

        $userC = new CmsUser;
        $userC->name = 'Jonathan';
        $userC->username = 'jwage';
        $userC->status = 'developer';
        $this->_em->persist($userC);

        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u IN (?0) OR u.username = ?1");
        $query->setParameter(0, [$userA, $userC]);
        $query->setParameter(1, 'beberlei');

        $users = $query->execute();

        self::assertEquals(2, count($users));
    }

    public function testQueryWithHiddenAsSelectExpression()
    {
        $userA = new CmsUser;
        $userA->name = 'Benjamin';
        $userA->username = 'beberlei';
        $userA->status = 'developer';
        $this->_em->persist($userA);

        $userB = new CmsUser;
        $userB->name = 'Roman';
        $userB->username = 'romanb';
        $userB->status = 'developer';
        $this->_em->persist($userB);

        $userC = new CmsUser;
        $userC->name = 'Jonathan';
        $userC->username = 'jwage';
        $userC->status = 'developer';
        $this->_em->persist($userC);

        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery("SELECT u, (SELECT COUNT(u2.id) FROM Doctrine\Tests\Models\CMS\CmsUser u2) AS HIDDEN total FROM Doctrine\Tests\Models\CMS\CmsUser u");
        $users = $query->execute();

        self::assertEquals(3, count($users));
        self::assertInstanceOf(CmsUser::class, $users[0]);
    }

    /**
     * @group DDC-1651
     */
    public function testSetParameterBindingSingleIdentifierObject()
    {
        $userC = new CmsUser;
        $userC->name = 'Jonathan';
        $userC->username = 'jwage';
        $userC->status = 'developer';
        $this->_em->persist($userC);

        $this->_em->flush();
        $this->_em->clear();

        $q = $this->_em->createQuery("SELECT DISTINCT u from Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1");
        $q->setParameter(1, $userC);

        self::assertEquals($userC, $q->getParameter(1)->getValue());

        // Parameter is not converted before, but it should be converted during execution. Test should not fail here
        $q->getResult();
    }

    /**
     * @group DDC-2319
     */
    public function testSetCollectionParameterBindingSingleIdentifierObject()
    {
        $u1 = new CmsUser;
        $u1->name = 'Name1';
        $u1->username = 'username1';
        $u1->status = 'developer';
        $this->_em->persist($u1);

        $u2 = new CmsUser;
        $u2->name = 'Name2';
        $u2->username = 'username2';
        $u2->status = 'tester';
        $this->_em->persist($u2);

        $u3 = new CmsUser;
        $u3->name = 'Name3';
        $u3->username = 'username3';
        $u3->status = 'tester';
        $this->_em->persist($u3);

        $this->_em->flush();
        $this->_em->clear();

        $userCollection = new ArrayCollection();

        $userCollection->add($u1);
        $userCollection->add($u2);
        $userCollection->add($u3->getId());

        $q = $this->_em->createQuery("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u IN (:users) ORDER BY u.id");
        $q->setParameter('users', $userCollection);
        $users = $q->execute();

        self::assertEquals(3, count($users));
        self::assertInstanceOf(CmsUser::class, $users[0]);
        self::assertInstanceOf(CmsUser::class, $users[1]);
        self::assertInstanceOf(CmsUser::class, $users[2]);

        $resultUser1 = $users[0];
        $resultUser2 = $users[1];
        $resultUser3 = $users[2];

        self::assertEquals($u1->username, $resultUser1->username);
        self::assertEquals($u2->username, $resultUser2->username);
        self::assertEquals($u3->username, $resultUser3->username);
    }

    /**
     * @group DDC-1822
     */
    public function testUnexpectedResultException()
    {
        $dql            = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u";
        $u1             = new CmsUser;
        $u2             = new CmsUser;
        $u1->name       = 'Fabio B. Silva';
        $u1->username   = 'FabioBatSilva';
        $u1->status     = 'developer';
        $u2->name       = 'Test';
        $u2->username   = 'test';
        $u2->status     = 'tester';

        try {
            $this->_em->createQuery($dql)->getSingleResult();
            $this->fail('Expected exception "\Doctrine\ORM\NoResultException".');
        } catch (UnexpectedResultException $exc) {
            self::assertInstanceOf('\Doctrine\ORM\NoResultException', $exc);
        }


        $this->_em->persist($u1);
        $this->_em->persist($u2);
        $this->_em->flush();
        $this->_em->clear();

        try {
            $this->_em->createQuery($dql)->getSingleResult();
            $this->fail('Expected exception "\Doctrine\ORM\NonUniqueResultException".');
        } catch (UnexpectedResultException $exc) {
            self::assertInstanceOf('\Doctrine\ORM\NonUniqueResultException', $exc);
        }
    }

    public function testMultipleJoinComponentsUsingInnerJoin()
    {
        $userA = new CmsUser;
        $userA->name = 'Benjamin';
        $userA->username = 'beberlei';
        $userA->status = 'developer';

        $phonenumberA = new CmsPhonenumber;
        $phonenumberA->phonenumber = '111111';
        $userA->addPhonenumber($phonenumberA);

        $userB = new CmsUser;
        $userB->name = 'Alexander';
        $userB->username = 'asm89';
        $userB->status = 'developer';

        $this->_em->persist($userA);
        $this->_em->persist($userB);
        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery("
            SELECT u, p
              FROM Doctrine\Tests\Models\CMS\CmsUser u
             INNER JOIN Doctrine\Tests\Models\CMS\CmsPhonenumber p WITH u = p.user
        ");
        $users = $query->execute();

        self::assertEquals(2, count($users));
        self::assertInstanceOf(CmsUser::class, $users[0]);
        self::assertInstanceOf(CmsPhonenumber::class, $users[1]);
    }

    public function testMultipleJoinComponentsUsingLeftJoin()
    {
        $userA = new CmsUser;
        $userA->name = 'Benjamin';
        $userA->username = 'beberlei';
        $userA->status = 'developer';

        $phonenumberA = new CmsPhonenumber;
        $phonenumberA->phonenumber = '111111';
        $userA->addPhonenumber($phonenumberA);

        $userB = new CmsUser;
        $userB->name = 'Alexander';
        $userB->username = 'asm89';
        $userB->status = 'developer';

        $this->_em->persist($userA);
        $this->_em->persist($userB);
        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery("
            SELECT u, p
              FROM Doctrine\Tests\Models\CMS\CmsUser u
              LEFT JOIN Doctrine\Tests\Models\CMS\CmsPhonenumber p WITH u = p.user
        ");
        $users = $query->execute();

        self::assertEquals(4, count($users));
        self::assertInstanceOf(CmsUser::class, $users[0]);
        self::assertInstanceOf(CmsPhonenumber::class, $users[1]);
        self::assertInstanceOf(CmsUser::class, $users[2]);
        self::assertNull($users[3]);
    }
}
