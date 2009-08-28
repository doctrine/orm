<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;

require_once __DIR__ . '/../../TestInit.php';

/**
 * NativeQueryTest
 *
 * @author robo
 */
class NativeQueryTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
        
        if ($this->_em->getConnection()->getDatabasePlatform()->getName() == 'oracle') {
            $this->markTestSkipped('The ' . __CLASS__ .' does not work with Oracle due to character casing.');
        }
    }

    public function testBasicNativeQuery()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'dev';
        $this->_em->persist($user);
        $this->_em->flush();
        
        $this->_em->clear();

        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'name', 'name');

        $query = $this->_em->createNativeQuery('SELECT id, name FROM cms_users WHERE username = ?', $rsm);
        $query->setParameter(1, 'romanb');

        $users = $query->getResult();

        $this->assertEquals(1, count($users));
        $this->assertTrue($users[0] instanceof CmsUser);
        $this->assertEquals('Roman', $users[0]->name);
    }
    
    public function testJoinedNativeQuery()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'dev';
        
        $phone = new CmsPhonenumber;
        $phone->phonenumber = 424242;
        
        $user->addPhonenumber($phone);
        
        $this->_em->persist($user);
        $this->_em->flush();
        
        $this->_em->clear();
        
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'name', 'name');
        $rsm->addFieldResult('u', 'status', 'status');
        $rsm->addJoinedEntityResult('Doctrine\Tests\Models\CMS\CmsPhonenumber', 'p', 'u', 'phonenumbers');
        $rsm->addFieldResult('p', 'phonenumber', 'phonenumber');
        
        $query = $this->_em->createNativeQuery('SELECT id, name, status, phonenumber FROM cms_users INNER JOIN cms_phonenumbers ON id = user_id WHERE username = ?', $rsm);
        $query->setParameter(1, 'romanb');

        $users = $query->getResult();
        $this->assertEquals(1, count($users));
        $this->assertTrue($users[0] instanceof CmsUser);
        $this->assertEquals('Roman', $users[0]->name);
        $this->assertTrue($users[0]->getPhonenumbers() instanceof \Doctrine\ORM\PersistentCollection);
        $this->assertEquals(1, count($users[0]->getPhonenumbers()));
        $phones = $users[0]->getPhonenumbers();
        $this->assertEquals(424242, $phones[0]->phonenumber);
        $this->assertTrue($phones[0]->getUser() === $users[0]);
        
    }
}

