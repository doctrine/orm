<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsGroup;

require_once __DIR__ . '/../../../TestInit.php';

class DDC849Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $user;
    private $group1;
    private $group2;
    
    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
        
        $this->user = new CmsUser();
        $this->user->username = "beberlei";
        $this->user->name = "Benjamin";
        $this->user->status = "active";
        
        $this->group1 = new CmsGroup();
        $this->group1->name = "Group 1";
        $this->group2 = new CmsGroup();
        $this->group2->name = "Group 2";
        
        $this->user->addGroup($this->group1);
        $this->user->addGroup($this->group2);
        
        $this->_em->persist($this->user);
        $this->_em->persist($this->group1);
        $this->_em->persist($this->group2);
        
        $this->_em->flush();
        $this->_em->clear();
        
        $this->user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->user->getId());
    }
    
    public function testRemoveContains()
    {
        $group1 = $this->user->groups[0];
        $group2 = $this->user->groups[1];
        
        $this->assertTrue($this->user->groups->contains($group1));
        $this->assertTrue($this->user->groups->contains($group2));
        
        $this->user->groups->removeElement($group1);
        $this->user->groups->remove(1);
        
        $this->assertFalse($this->user->groups->contains($group1));
        $this->assertFalse($this->user->groups->contains($group2));
    }
    
    public function testClearCount()
    {
        $this->user->addGroup(new CmsGroup);
        $this->assertEquals(3, count($this->user->groups));
        
        $this->user->groups->clear();
        
        $this->assertEquals(0, $this->user->groups->count());
        $this->assertEquals(0, count($this->user->groups));
    }
    
    public function testClearContains()
    {
        $group1 = $this->user->groups[0];
        $group2 = $this->user->groups[1];
        
        $this->assertTrue($this->user->groups->contains($group1));
        $this->assertTrue($this->user->groups->contains($group2));
        
        $this->user->groups->clear();
        
        $this->assertFalse($this->user->groups->contains($group1));
        $this->assertFalse($this->user->groups->contains($group2));
    }
}