<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Common\Cache\ArrayCache;

require_once __DIR__ . '/../../TestInit.php';

/**
 * ResultCacheTest
 *
 * @author robo
 */
class ResultCacheTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testQueryCache()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'dev';
        $this->_em->persist($user);
        $this->_em->flush();


        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $cache = new ArrayCache;
        $query->setResultCache($cache);
		$this->assertEquals(0, $cache->count());
		
		$initialQueryCount = $this->_em->getConnection()->getQueryCount();
		
        $users = $query->getResult();

        $this->assertEquals($initialQueryCount + 1, $this->_em->getConnection()->getQueryCount());
       	$this->assertEquals(1, $cache->count());
        $this->assertEquals(1, count($users));
        $this->assertEquals('Roman', $users[0]->name);
        
        $this->_em->clear();
        
        $query2 = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $query2->setResultCache($cache);
        
        $users = $query2->getResult();

        $this->assertEquals($initialQueryCount + 1, $this->_em->getConnection()->getQueryCount());
       	$this->assertEquals(1, $cache->count());
        $this->assertEquals(1, count($users));
        $this->assertEquals('Roman', $users[0]->name);
    }
}

