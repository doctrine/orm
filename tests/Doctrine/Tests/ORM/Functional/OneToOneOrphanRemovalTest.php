<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser,
    Doctrine\Tests\Models\CMS\CmsAddress,
    Doctrine\Tests\Models\CMS\CmsPhonenumber;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests a bidirectional one-to-one association mapping with orphan removal.
 */
class OneToOneOrphanRemovalTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        
        parent::setUp();
    }
    
    public function testOrphanRemoval()
    {
        $user = new CmsUser;
        $user->status = 'dev';
        $user->username = 'romanb';
        $user->name = 'Roman B.';
        
        $address = new CmsAddress;
        $address->country = 'de';
        $address->zip = 1234;
        $address->city = 'Berlin';
        
        $user->setAddress($address);
        
        $this->_em->persist($user);
        $this->_em->flush();
        
        $userId = $user->getId();
        
        $this->_em->clear();
        
        $userProxy = $this->_em->getReference('Doctrine\Tests\Models\CMS\CmsUser', $userId);
        
        $this->_em->remove($userProxy);
        $this->_em->flush();
        $this->_em->clear();
        
        $query  = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u');
        $result = $query->getResult();
        
        $this->assertEquals(0, count($result), 'CmsUser should be removed by EntityManager');
        
        $query  = $this->_em->createQuery('SELECT a FROM Doctrine\Tests\Models\CMS\CmsAddress a');
        $result = $query->getResult();
        
        $this->assertEquals(0, count($result), 'CmsAddress should be removed by orphanRemoval');
    }
}