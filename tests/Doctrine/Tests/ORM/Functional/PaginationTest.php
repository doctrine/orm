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

    public function testCountSimpleWithoutJoin()
    {
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseSqlWalkers(false);
        $this->assertEquals(3, count($paginator));
    }

    public function testCountWithFetchJoin()
    {
        $dql = "SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseSqlWalkers(false);
        $this->assertEquals(3, count($paginator));
    }

    public function testCountComplexWithSqlWalker()
    {
        $dql = "SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g.id HAVING userCount > 0";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseSqlWalkers(true);
        $this->assertEquals(9, count($paginator));
    }

    public function testIterateSimpleWithoutJoinFetchJoinHandlingOff()
    {
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, false);
        $paginator->setUseSqlWalkers(false);

        $data = array();
        foreach ($paginator as $user) {
            $data[] = $user;
        }
        $this->assertEquals(3, count($data));
    }

    public function testIterateSimpleWithoutJoinFetchJoinHandlingOn()
    {
        $dql = "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, true);
        $paginator->setUseSqlWalkers(false);

        $data = array();
        foreach ($paginator as $user) {
            $data[] = $user;
        }
        $this->assertEquals(3, count($data));
    }

    public function testIterateWithFetchJoin()
    {
        $dql = "SELECT u,g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query, true);
        $paginator->setUseSqlWalkers(false);

        $data = array();
        foreach ($paginator as $user) {
            $data[] = $user;
        }
        $this->assertEquals(3, count($data));
    }

    public function testIterateComplexWithSqlWalker()
    {
        $dql = "SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g.id HAVING userCount > 0";
        $query = $this->_em->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->setUseSqlWalkers(true);

        $data = array();
        foreach ($paginator as $user) {
            $data[] = $user;
        }
        $this->assertEquals(9, count($data));
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

        try {
            count($paginator);
            $this->fail('Paginator did not detect custom SQL walker');
        } catch (\PHPUnit_Framework_Error_Notice $e) {
            $this->assertEquals('Undefined index: userCount', $e->getMessage());
        }
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
}
