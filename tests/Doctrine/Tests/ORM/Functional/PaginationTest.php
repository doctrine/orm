<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Query;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsComment;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @group DDC-1613
 */
class PaginationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
        $this->populate();
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testCountSimpleWithoutJoin($useOutputWalkers)
    {
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(3, $paginator);
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testCountWithFetchJoin($useOutputWalkers)
    {
        $dql = "SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(3, $paginator);
    }

    public function testCountComplexWithOutputWalker()
    {
        $dql = "SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g HAVING COUNT(u.id) > 0";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(true);
        $this->assertCount(9, $paginator);
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateSimpleWithoutJoinFetchJoinHandlingOff($useOutputWalkers)
    {
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, false);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(3, $paginator->getIterator());
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateSimpleWithoutJoinFetchJoinHandlingOn($useOutputWalkers)
    {
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(3, $paginator->getIterator());
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoin($useOutputWalkers)
    {
        $dql = "SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(3, $paginator->getIterator());
    }

    public function testIterateComplexWithOutputWalker()
    {
        $dql = "SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g HAVING COUNT(u.id) > 0";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(true);
        $this->assertCount(9, $paginator->getIterator());
    }

    public function testDetectOutputWalker()
    {
        // This query works using the output walkers but causes an exception using the TreeWalker
        $dql = "SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g HAVING COUNT(u.id) > 0";
        $query = $this->_em->createQuery($dql);

        // If the Paginator detects the custom output walker it should fall back to using the
        // Tree walkers for pagination, which leads to an exception. If the query works, the output walkers were used
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Query\SqlWalker');
        $paginator = new Paginator($query);

        $this->setExpectedException(
            'RuntimeException',
            'Cannot count query that uses a HAVING clause. Use the output walkers for pagination'
        );

        count($paginator);
    }

    public function testCloneQuery()
    {
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->getIterator();

        $this->assertTrue($query->getParameters()->isEmpty());
    }

    public function testQueryWalkerIsKept()
    {
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u";
        $query = $this->_em->createQuery($dql);
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\Tests\ORM\Functional\CustomPaginationTestTreeWalker'));

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers(false);
        $this->assertCount(1, $paginator->getIterator());
        $this->assertEquals(1, $paginator->count());
    }
    
    public function testCountQuery_ParametersInSelect()
    {
        /** @var $query Query */
        $query = $this->_em->createQuery(
            'SELECT u, (CASE WHEN u.id < :vipMaxId THEN 1 ELSE 0 END) AS hidden promotedFirst FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id < :id ORDER BY promotedFirst'
        );
        $query->setParameter('vipMaxId', 10);
        $query->setParameter('id', 100);
        $query->setFirstResult(null)->setMaxResults(null);

        $this->assertEquals(
            "SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, (CASE WHEN c0_.id < ? THEN 1 ELSE 0 END) AS sclr4, c0_.email_id AS email_id5 FROM cms_users c0_ WHERE c0_.id < ? ORDER BY sclr4 ASC",
            $query->getSQL()
        );
        $paginator = new Paginator($query);
        $countQuery = $paginator->getCountQuery();

        $this->assertEquals(
            "SELECT COUNT(*) AS dctrn_count FROM (SELECT DISTINCT id0 FROM (SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3, (CASE WHEN c0_.id < ? THEN 1 ELSE 0 END) AS sclr4, c0_.email_id AS email_id5 FROM cms_users c0_ WHERE c0_.id < ? ORDER BY sclr4 ASC) dctrn_result) dctrn_table",
            $countQuery->getSQL()
        );
        $this->assertEquals(2, count($countQuery->getParameters()));
        $this->assertEquals(3, $paginator->count());


        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Query\SqlWalker');
        $paginator = new Paginator($query);
        $countQuery = $paginator->getCountQuery();
        $this->assertEquals(
            "SELECT count(DISTINCT c0_.id) AS sclr0 FROM cms_users c0_ WHERE c0_.id < ?",
            $countQuery->getSQL()
        );
        //if select part of query is replaced with count(...) paginator should remove parameters from query object not used in new query.
        $this->assertEquals(1, count($countQuery->getParameters()));
        $this->assertEquals(3, $paginator->count());
    }

    public function populate()
    {
        for ($i = 0; $i < 3; $i++) {
            $user = new CmsUser();
            $user->name = "Name$i";
            $user->username = "username$i";
            $user->status = "active";
            $this->_em->persist($user);

            for ($j = 0; $j < 3; $j++) {;
                $group = new CmsGroup();
                $group->name = "group$j";
                $user->addGroup($group);
                $this->_em->persist($group);
            }
        }
        $this->_em->flush();
    }

    public function useOutputWalkers()
    {
        return array(
            array(true),
            array(false),
        );
    }
}

class CustomPaginationTestTreeWalker extends Query\TreeWalkerAdapter
{
    public function walkSelectStatement(Query\AST\SelectStatement $selectStatement)
    {
        $condition = new Query\AST\ConditionalPrimary();

        $path = new Query\AST\PathExpression(Query\AST\PathExpression::TYPE_STATE_FIELD, 'u', 'name');
        $path->type = Query\AST\PathExpression::TYPE_STATE_FIELD;

        $condition->simpleConditionalExpression = new Query\AST\ComparisonExpression(
            $path,
            '=',
            new Query\AST\Literal(Query\AST\Literal::STRING, 'Name1')
        );

        $selectStatement->whereClause = new Query\AST\WhereClause($condition);
    }
}
