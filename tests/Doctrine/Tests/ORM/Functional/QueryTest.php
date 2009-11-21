<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsArticle;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Functional Query tests.
 *
 * @author robo
 */
class QueryTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }
    
    /**
     * @expectedException Doctrine\ORM\Query\QueryException
     */
    public function testParameterIndexZeroThrowsException()
    {
        $query = $this->_em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u where u.username = ?1");
        $query->execute(array(42)); // same as array(0 => 42), 0 is invalid parameter position
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
        
        $this->assertEquals(1, count($result));
        $this->assertTrue($result[0][0] instanceof CmsUser);
        $this->assertEquals('Guilherme', $result[0][0]->name);
        $this->assertEquals('gblanco', $result[0][0]->username);
        $this->assertEquals('developer', $result[0][0]->status);
        $this->assertEquals('GUILHERME', $result[0][1]);

        $resultArray = $query->getArrayResult();
        $this->assertEquals(1, count($resultArray));
        $this->assertTrue(is_array($resultArray[0][0]));
        $this->assertEquals('Guilherme', $resultArray[0][0]['name']);
        $this->assertEquals('gblanco', $resultArray[0][0]['username']);
        $this->assertEquals('developer', $resultArray[0][0]['status']);
        $this->assertEquals('GUILHERME', $resultArray[0][1]);

        $scalarResult = $query->getScalarResult();
        $this->assertEquals(1, count($scalarResult));
        $this->assertEquals('Guilherme', $scalarResult[0]['u_name']);
        $this->assertEquals('gblanco', $scalarResult[0]['u_username']);
        $this->assertEquals('developer', $scalarResult[0]['u_status']);
        $this->assertEquals('GUILHERME', $scalarResult[0][1]);

        $query = $this->_em->createQuery("select upper(u.name) from Doctrine\Tests\Models\CMS\CmsUser u where u.username = 'gblanco'");
        $this->assertEquals('GUILHERME', $query->getSingleScalarResult());
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

        $query = $this->_em->createQuery("select u, a from Doctrine\Tests\Models\CMS\CmsUser u join u.articles a");
        $users = $query->getResult();
        $this->assertEquals(1, count($users));
        $this->assertTrue($users[0] instanceof CmsUser);
        $this->assertEquals(2, count($users[0]->articles));
        $this->assertEquals('Doctrine 2', $users[0]->articles[0]->topic);
        $this->assertEquals('Symfony 2', $users[0]->articles[1]->topic);
    }

    public function testUsingUnknownQueryParameterShouldThrowException()
    {
        $this->setExpectedException(
            "Doctrine\ORM\Query\QueryException",
            "Invalid parameter: token 2 is not defined in the query."
        );

        $q = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name = ?1');
        $q->setParameter(2, 'jwage');
        $user = $q->getSingleResult();
    }

    public function testMismatchingParamExpectedParamCount()
    {
        $this->setExpectedException(
            "Doctrine\ORM\Query\QueryException",
            "Invalid parameter number: number of bound variables does not match number of tokens"
        );

        $q = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name = ?1');
        $q->setParameter(1, 'jwage');
        $q->setParameter(2, 'jwage');

        $user = $q->getSingleResult();
    }

    public function testInvalidInputParameterThrowsException()
    {
        $this->setExpectedException("Doctrine\ORM\Query\QueryException");

        $q = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name = ?');
        $q->setParameter(1, 'jwage');
        $user = $q->getSingleResult();
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

        $query = $this->_em->createQuery("select a from Doctrine\Tests\Models\CMS\CmsArticle a");
        $articles = $query->iterate();

        $iteratedCount = 0;
        $topics = array();
        foreach($articles AS $row) {
            $article = $row[0];
            $topics[] = $article->topic;

            $identityMap = $this->_em->getUnitOfWork()->getIdentityMap();
            $identityMapCount = count($identityMap['Doctrine\Tests\Models\CMS\CmsArticle']);
            $this->assertTrue($identityMapCount>$iteratedCount);
            
            $iteratedCount++;
        }

        $this->assertEquals(array("Doctrine 2", "Symfony 2"), $topics);
        $this->assertEquals(2, $iteratedCount);

        $this->_em->flush();
        $this->_em->clear();
    }

    public function testFluentQueryInterface()
    {
        $q = $this->_em->createQuery("select a from Doctrine\Tests\Models\CMS\CmsArticle a");
        $q2 = $q->expireQueryCache(true)
          ->setQueryCacheLifetime(3600)
          ->setQueryCacheDriver(null)
          ->expireResultCache(true)
          ->setHint('foo', 'bar')
          ->setHint('bar', 'baz')
          ->setParameter(1, 'bar')
          ->setParameters(array(2 => 'baz'))
          ->setResultCacheDriver(null)
          ->setResultCacheId('foo')
          ->setDql('foo')
          ->setFirstResult(10)
          ->setMaxResults(10);

        $this->assertSame($q2, $q);
    }
}

