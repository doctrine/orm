<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\Group;
use Doctrine\Tests\Models\Quote\User;

/**
 * @group DDC-1845
 * @group DDC-1885
 */
class DDC1885Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    /**
     * @var \Doctrine\Tests\Models\Quote\User
     */
    private $user;

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\User'),
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\Group'),
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\Address'),
            ));
        } catch(\Exception $e) {
        }

        $user           = new User();
        $user->name     = "FabioBatSilva";
        $user->email    = "fabio.bat.silva@gmail.com";
        $user->groups[] = new Group('G 1');
        $user->groups[] = new Group('G 2');
        $this->user     = $user;

        // Create
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();
        
    }

    public function testCreateRetreaveUpdateDelete()
    {
        $user   = $this->user;
        $g1     = $user->getGroups()->get(0);
        $g2     = $user->getGroups()->get(1);

        $u1Id   = $user->id;
        $g1Id   = $g1->id;
        $g2Id   = $g2->id;

        // Retreave
        $user = $this->_em->find('Doctrine\Tests\Models\Quote\User', $u1Id);

        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\User', $user);
        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals($u1Id, $user->id);
        
        $this->assertCount(2, $user->groups);

        $g1 = $user->getGroups()->get(0);
        $g2 = $user->getGroups()->get(1);

        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $g1);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $g2);

        $g1->name = 'Bar 11';
        $g2->name = 'Foo 22';

        // Update
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find('Doctrine\Tests\Models\Quote\User', $u1Id);

        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\User', $user);
        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals($u1Id, $user->id);

        // Delete
        $this->_em->remove($user);
        
        $this->_em->flush();
        $this->_em->clear();

        $this->assertNull($this->_em->find('Doctrine\Tests\Models\Quote\User', $u1Id));
        $this->assertNull($this->_em->find('Doctrine\Tests\Models\Quote\Group', $g1Id));
        $this->assertNull($this->_em->find('Doctrine\Tests\Models\Quote\Group', $g2Id));
    }

    public function testRemoveItem()
    {
        $user   = $this->user;
        $u1Id   = $user->id;
        $user   = $this->_em->find('Doctrine\Tests\Models\Quote\User', $u1Id);

        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\User', $user);
        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals($u1Id, $user->id);

        $this->assertCount(2, $user->groups);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $user->getGroups()->get(0));
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $user->getGroups()->get(1));

        $user->getGroups()->remove(0);

        // Update
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find('Doctrine\Tests\Models\Quote\User', $u1Id);

        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\User', $user);
        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals($u1Id, $user->id);

        $this->assertCount(1, $user->getGroups());
    }

    public function testClearAll()
    {
        $user   = $this->user;
        $u1Id   = $user->id;
        $user   = $this->_em->find('Doctrine\Tests\Models\Quote\User', $u1Id);

        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\User', $user);
        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals($u1Id, $user->id);

        $this->assertCount(2, $user->groups);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $user->getGroups()->get(0));
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $user->getGroups()->get(1));

        $user->getGroups()->clear();

        // Update
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find('Doctrine\Tests\Models\Quote\User', $u1Id);

        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\User', $user);
        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals($u1Id, $user->id);

        $this->assertCount(0, $user->getGroups());
    }

    public function testCountExtraLazy()
    {
        $user   = $this->user;
        $u1Id   = $user->id;
        $user   = $this->_em->find('Doctrine\Tests\Models\Quote\User', $u1Id);

        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\User', $user);
        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals($u1Id, $user->id);

        $this->assertCount(0, $user->extraLazyGroups);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $user->getGroups()->get(0));
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Group', $user->getGroups()->get(1));
    }
}
