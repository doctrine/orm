<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Description of DetachedEntityTest
 *
 * @author robo
 */
class EntityRepositoryTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testBasicFinders() {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'freak';
        $this->_em->persist($user);

        $user2 = new CmsUser;
        $user2->name = 'Guilherme';
        $user2->username = 'gblanco';
        $user2->status = 'dev';
        $this->_em->persist($user2);

        $this->_em->flush();
        $user1Id = $user->getId();
        unset($user);
        unset($user2);
        $this->_em->clear();

        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $user = $repos->find($user1Id);
        $this->assertTrue($user instanceof CmsUser);
        $this->assertEquals('Roman', $user->name);
        $this->assertEquals('freak', $user->status);

        $this->_em->clear();

        $users = $repos->findBy(array('status' => 'dev'));
        $this->assertEquals(1, count($users));
        $this->assertTrue($users[0] instanceof CmsUser);
        $this->assertEquals('Guilherme', $users[0]->name);
        $this->assertEquals('dev', $users[0]->status);

        $this->_em->clear();

        $users = $repos->findByStatus('dev');
        $this->assertEquals(1, count($users));
        $this->assertTrue($users[0] instanceof CmsUser);
        $this->assertEquals('Guilherme', $users[0]->name);
        $this->assertEquals('dev', $users[0]->status);

        $this->_em->clear();

        $users = $repos->findAll();
        $this->assertEquals(2, count($users));

        $this->_em->clear();

        $this->_em->getConfiguration()->addEntityNamespace('CMS', 'Doctrine\Tests\Models\CMS');

        $repos = $this->_em->getRepository('CMS:CmsUser');

        $users = $repos->findAll();
        $this->assertEquals(2, count($users));

        $this->_em->getConfiguration()->setEntityNamespaces(array());
    }

    /**
     * @expectedException \Doctrine\ORM\ORMException
     */
    public function testExceptionIsThrownWhenCallingFindByWithoutParameter() {
        $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser')
                  ->findByStatus();
    }

    /**
     * @expectedException \Doctrine\ORM\ORMException
     */
    public function testExceptionIsThrownWhenUsingInvalidFieldName() {
        $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser')
                  ->findByThisFieldDoesNotExist('testvalue');
    }
}

