<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;

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

    public function testSimpleQueries()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';
        $this->_em->save($user);
        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery("select u, upper(u.name) from Doctrine\Tests\Models\CMS\CmsUser u where u.username = 'gblanco'");

        $result = $query->getResultList();

        $this->assertEquals(1, count($result));
        $this->assertTrue($result[0][0] instanceof CmsUser);
        $this->assertEquals('Guilherme', $result[0][0]->name);
        $this->assertEquals('gblanco', $result[0][0]->username);
        $this->assertEquals('developer', $result[0][0]->status);
        $this->assertEquals('GUILHERME', $result[0][1]);

        $resultArray = $query->getResultArray();
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
        $this->assertEquals('GUILHERME', $scalarResult[0]['dctrn_1']);

        $query = $this->_em->createQuery("select upper(u.name) from Doctrine\Tests\Models\CMS\CmsUser u where u.username = 'gblanco'");
        $this->assertEquals('GUILHERME', $query->getSingleScalarResult());
    }

}

