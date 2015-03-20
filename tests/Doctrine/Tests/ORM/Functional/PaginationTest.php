<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\Models\Pagination\Company;
use Doctrine\Tests\Models\Pagination\Logo;
use ReflectionMethod;

/**
 * @group DDC-1613
 */
class PaginationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        $this->useModelSet('pagination');
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
        $this->assertCount(9, $paginator);
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
        $this->assertCount(9, $paginator);
    }

    public function testCountComplexWithOutputWalker()
    {
        $dql = "SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g HAVING COUNT(u.id) > 0";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(true);
        $this->assertCount(3, $paginator);
    }

    /**
     * @expectedException
     */
    public function testCountComplexWithoutOutputWalker()
    {
        $dql = "SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g HAVING COUNT(u.id) > 0";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(false);

        $this->setExpectedException(
            'RuntimeException',
            'Cannot count query that uses a HAVING clause. Use the output walkers for pagination'
        );
        $this->assertCount(3, $paginator);
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testCountWithComplexScalarOrderBy($useOutputWalkers)
    {
        $dql = 'SELECT l FROM Doctrine\Tests\Models\Pagination\Logo l ORDER BY l.image_width * l.image_height DESC';
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(9, $paginator);
    }

    /**
     * @dataProvider useOutputWalkersAndFetchJoinCollection
     */
    public function testIterateSimpleWithoutJoin($useOutputWalkers, $fetchJoinCollection)
    {
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(9, $paginator->getIterator());

        // Test with limit
        $query->setMaxResults(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(3, $paginator->getIterator());

        // Test with limit and offset
        $query->setMaxResults(3)->setFirstResult(4);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $this->assertCount(3, $paginator->getIterator());
    }

    private function iterateWithOrder($useOutputWalkers, $fetchJoinCollection, $baseDql, $checkField)
    {
        // Ascending
        $dql = "$baseDql ASC";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        $this->assertCount(9, $iter);
        $result = iterator_to_array($iter);
        $this->assertEquals($checkField . "0", $result[0]->$checkField);

        // With limit
        $query->setMaxResults(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        $this->assertCount(3, $iter);
        $result = iterator_to_array($iter);
        $this->assertEquals($checkField . "0", $result[0]->$checkField);

        // With offset
        $query->setMaxResults(3)->setFirstResult(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        $this->assertCount(3, $iter);
        $result = iterator_to_array($iter);
        $this->assertEquals($checkField . "3", $result[0]->$checkField);

        // Descending
        $dql = "$baseDql DESC";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        $this->assertCount(9, $iter);
        $result = iterator_to_array($iter);
        $this->assertEquals($checkField . "8", $result[0]->$checkField);

        // With limit
        $query->setMaxResults(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        $this->assertCount(3, $iter);
        $result = iterator_to_array($iter);
        $this->assertEquals($checkField . "8", $result[0]->$checkField);

        // With offset
        $query->setMaxResults(3)->setFirstResult(3);
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        $iter = $paginator->getIterator();
        $this->assertCount(3, $iter);
        $result = iterator_to_array($iter);
        $this->assertEquals($checkField . "5", $result[0]->$checkField);
    }

    /**
     * @dataProvider useOutputWalkersAndFetchJoinCollection
     */
    public function testIterateSimpleWithoutJoinWithOrder($useOutputWalkers, $fetchJoinCollection)
    {
        // Ascending
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username";
        $this->iterateWithOrder($useOutputWalkers, $fetchJoinCollection, $dql, "username");
    }

    /**
     * @dataProvider fetchJoinCollection
     */
    public function testIterateSimpleWithOutputWalkerWithoutJoinWithComplexOrder($fetchJoinCollection)
    {
        // Ascending
        $dql = "SELECT l FROM Doctrine\Tests\Models\Pagination\Logo l ORDER BY l.image_width * l.image_height";
        $this->iterateWithOrder(true, $fetchJoinCollection, $dql, "image");
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
        $this->assertCount(9, $paginator->getIterator());
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoinWithOrder($useOutputWalkers)
    {
        $dql = 'SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g ORDER BY u.username';
        $this->iterateWithOrder($useOutputWalkers, true, $dql, "username");
    }

    /**
     * @dataProvider useOutputWalkersAndFetchJoinCollection
     */
    public function testIterateWithRegularJoinWithOrderByColumnFromJoined($useOutputWalkers, $fetchJoinCollection)
    {
        $dql = 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.email e ORDER BY e.email';
        $this->iterateWithOrder($useOutputWalkers, $fetchJoinCollection, $dql, "username");
    }

    /**
     * @dataProvider fetchJoinCollection
     */
    public function testIterateWithOutputWalkersWithRegularJoinWithComplexOrderByReferencingJoined($fetchJoinCollection)
    {
        // long function name is loooooooooooong

        $dql = "SELECT c FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.logo l ORDER BY l.image_height * l.image_width";
        $this->iterateWithOrder(true, $fetchJoinCollection, $dql, "name");
    }

    /**
     * @dataProvider useOutputWalkers
     */
    public function testIterateWithFetchJoinWithOrderByColumnFromJoined($useOutputWalkers)
    {
        $dql = "SELECT u,g,e FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g JOIN u.email e ORDER BY e.email";
        $this->iterateWithOrder($useOutputWalkers, true, $dql, "username");
    }

    /**
     * @dataProvider fetchJoinCollection
     */
    public function testIterateWithOutputWalkersWithFetchJoinWithComplexOrderByReferencingJoined($fetchJoinCollection)
    {
        $dql = "SELECT c,l FROM Doctrine\Tests\Models\Pagination\Company c JOIN c.logo l ORDER BY l.image_width * l.image_height";
        $this->iterateWithOrder(true, $fetchJoinCollection, $dql, "name");
    }

    public function testIterateComplexWithOutputWalker()
    {
        $dql = "SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g HAVING COUNT(u.id) > 0";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(true);
        $this->assertCount(3, $paginator->getIterator());
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
    
    public function testCountQueryStripsParametersInSelect()
    {
        $query = $this->_em->createQuery(
            'SELECT u, (CASE WHEN u.id < :vipMaxId THEN 1 ELSE 0 END) AS hidden promotedFirst
            FROM Doctrine\\Tests\\Models\\CMS\\CmsUser u
            WHERE u.id < :id or 1=1'
        );
        $query->setParameter('vipMaxId', 10);
        $query->setParameter('id', 100);
        $query->setFirstResult(null)->setMaxResults(null);

        $paginator = new Paginator($query);

        $getCountQuery = new ReflectionMethod($paginator, 'getCountQuery');

        $getCountQuery->setAccessible(true);

        $this->assertCount(2, $getCountQuery->invoke($paginator)->getParameters());
        $this->assertCount(9, $paginator);

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Query\SqlWalker');

        $paginator = new Paginator($query);

        // if select part of query is replaced with count(...) paginator should remove
        // parameters from query object not used in new query.
        $this->assertCount(1, $getCountQuery->invoke($paginator)->getParameters());
        $this->assertCount(9, $paginator);
    }

    public function populate()
    {
        $groups = [];
        for ($j = 0; $j < 3; $j++) {;
            $group = new CmsGroup();
            $group->name = "group$j";
            $groups[] = $group;
            $this->_em->persist($group);
        }

        for ($i = 0; $i < 9; $i++) {
            $user = new CmsUser();
            $user->name = "Name$i";
            $user->username = "username$i";
            $user->status = "active";
            $user->email = new CmsEmail();
            $user->email->user = $user;
            $user->email->email = "email$i";
            for ($j = 0; $j < 3; $j++) {
                $user->addGroup($groups[$j]);
            }
            $this->_em->persist($user);
        }

        for ($i = 0; $i < 9; $i++) {
            $company = new Company();
            $company->name = "name$i";
            $company->logo = new Logo();
            $company->logo->image = "image$i";
            $company->logo->image_width = 100 + $i;
            $company->logo->image_height = 100 + $i;
            $company->logo->company = $company;
            $this->_em->persist($company);
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

    public function fetchJoinCollection()
    {
        return array(
            array(true),
            array(false),
        );
    }

    public function useOutputWalkersAndFetchJoinCollection()
    {
        return array(
            array(true, false),
            array(true, true),
            array(false, false),
            array(false, true),
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
