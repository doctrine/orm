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
