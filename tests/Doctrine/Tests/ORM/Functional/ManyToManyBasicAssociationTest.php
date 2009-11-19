<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser,
    Doctrine\Tests\Models\CMS\CmsGroup;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Basic many-to-many association tests.
 * ("Working with associations")
 * 
 * @author robo
 */
class ManyToManyBasicAssociationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }
    
    public function testManyToManyAddRemove()
    {
        // Set up user with 2 groups
        $user = new CmsUser;
        $user->username = 'romanb';
        $user->status = 'dev';
        $user->name = 'Roman B.';
        
        $group1 = new CmsGroup;
        $group1->name = 'Developers';
        
        $group2 = new CmsGroup;
        $group2->name = 'Humans';
        
        $user->addGroup($group1);
        $user->addGroup($group2);
        
        $this->_em->persist($user); // cascades to groups
        $this->_em->flush();
        
        $this->_em->clear();
        
        $uRep = $this->_em->getRepository(get_class($user));
    
        // Get user
        $user = $uRep->findOneById($user->getId());
    
        $this->assertFalse($user->getGroups()->isInitialized());
        
        // Check groups
        $this->assertEquals(2, $user->getGroups()->count());
    
        $this->assertTrue($user->getGroups()->isInitialized());
        
        // Remove first group
        unset($user->groups[0]);
        //$user->getGroups()->remove(0);
    
        $this->_em->flush();
        $this->_em->clear();
    
        // Reload same user
        $user2 = $uRep->findOneById($user->getId());
    
        // Check groups
        $this->assertEquals(1, $user2->getGroups()->count());        
    }
    
    public function testManyToManyInverseSideIgnored()
    {
        $user = new CmsUser;
        $user->username = 'romanb';
        $user->status = 'dev';
        $user->name = 'Roman B.';
        
        $group = new CmsGroup;
        $group->name = 'Humans';
        
        // modify directly, addUser() would also (properly) set the owning side
        $group->users[] = $user;
        
        $this->_em->persist($user);
        $this->_em->persist($group);
        $this->_em->flush();
        $this->_em->clear();
        
        // Association should not exist
        $user2 = $this->_em->find(get_class($user), $user->getId());
        $this->assertEquals(0, $user2->getGroups()->count());
    }
    
}
