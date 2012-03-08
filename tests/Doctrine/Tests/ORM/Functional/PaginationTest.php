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
     * @dataProvider useSqlWalkers
     */
    public function testCountSimpleWithoutJoin($useSqlWalkers)
    {
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseSqlWalkers($useSqlWalkers);
        $this->assertCount(3, $paginator);
    }

    /**
     * @dataProvider useSqlWalkers
     */
    public function testCountWithFetchJoin($useSqlWalkers)
    {
        $dql = "SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseSqlWalkers($useSqlWalkers);
        $this->assertCount(3, $paginator);
    }

    public function testCountComplexWithSqlWalker()
    {
        $dql = "SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g.id HAVING userCount > 0";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseSqlWalkers(true);
        $this->assertCount(9, $paginator);
    }

    /**
     * @dataProvider useSqlWalkers
     */
    public function testIterateSimpleWithoutJoinFetchJoinHandlingOff($useSqlWalkers)
    {
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, false);
        $paginator->setUseSqlWalkers($useSqlWalkers);
        $this->assertCount(3, $paginator->getIterator());
    }

    /**
     * @dataProvider useSqlWalkers
     */
    public function testIterateSimpleWithoutJoinFetchJoinHandlingOn($useSqlWalkers)
    {
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, true);
        $paginator->setUseSqlWalkers($useSqlWalkers);
        $this->assertCount(3, $paginator->getIterator());
    }

    /**
     * @dataProvider useSqlWalkers
     */
    public function testIterateWithFetchJoin($useSqlWalkers)
    {
        $dql = "SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, true);
        $paginator->setUseSqlWalkers($useSqlWalkers);
        $this->assertCount(3, $paginator->getIterator());
    }

    public function testIterateComplexWithSqlWalker()
    {
        $dql = "SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g.id HAVING userCount > 0";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseSqlWalkers(true);
        $this->assertCount(9, $paginator->getIterator());
    }

    public function testDetectSqlWalker()
    {
        // This query works using the SQL walkers but causes an exception using the TreeWalker
        $dql = "SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g.id HAVING userCount > 0";
        $query = $this->_em->createQuery($dql);

        // If the Paginator detects the custom SQL walker it should fall back to using the
        // Tree walkers for pagination, which leads to an exception. If the query works, the SQL walkers were used
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Query\SqlWalker');
        $paginator = new Paginator($query);

        $this->setExpectedException(
            'RuntimeException',
            'Cannot count query that uses a HAVING clause. Use the SQL walkers for pagination'
        );

        count($paginator);
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

    public function useSqlWalkers()
    {
        return array(
            array(true),
            array(false),
        );
    }
}
