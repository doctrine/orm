<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\ORM\UnitOfWork;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Description of DetachedEntityTest
 *
 * @author robo
 */
class DetachedEntityTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testSimpleDetachMerge() {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'dev';
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        // $user is now detached

        $this->assertFalse($this->_em->contains($user));

        $user->name = 'Roman B.';

        //$this->assertEquals(UnitOfWork::STATE_DETACHED, $this->_em->getUnitOfWork()->getEntityState($user));

        $user2 = $this->_em->merge($user);

        $this->assertFalse($user === $user2);
        $this->assertTrue($this->_em->contains($user2));
        $this->assertEquals('Roman B.', $user2->name);
    }
    
    public function testSerializeUnserializeModifyMerge()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';
        
        $ph1 = new CmsPhonenumber;
        $ph1->phonenumber = 1234;
        $user->addPhonenumber($ph1);

        $this->_em->persist($user);
        $this->_em->flush();
        $this->assertTrue($this->_em->contains($user));
        
        $serialized = serialize($user);
        $this->_em->clear();
        $this->assertFalse($this->_em->contains($user));        
        unset($user);
        
        $user = unserialize($serialized);
        
        $ph2 = new CmsPhonenumber;
        $ph2->phonenumber = 56789;
        $user->addPhonenumber($ph2);
        $this->assertEquals(2, count($user->getPhonenumbers()));
        $this->assertFalse($this->_em->contains($user));
        
        $this->_em->persist($ph2);
        
        // Merge back in
        $user = $this->_em->merge($user); // merge cascaded to phonenumbers
        $this->_em->flush();
        
        $this->assertTrue($this->_em->contains($user));
        $this->assertEquals(2, count($user->getPhonenumbers()));
        $phonenumbers = $user->getPhonenumbers();
        $this->assertTrue($this->_em->contains($phonenumbers[0]));
        $this->assertTrue($this->_em->contains($phonenumbers[1]));
    }

}

