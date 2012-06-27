<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\Group;
use Doctrine\Tests\Models\Quote\User;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1845
 * @group DDC-1885
 */
class DDC1885Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

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
    }

    public function testCreateRetreaveUpdateDelete()
    {

        $g1     = new Group('G 1');
        $g2     = new Group('G 2');
        $user   = new User();

        $user->name     = "FabioBatSilva";
        $user->email    = "fabio.bat.silva@gmail.com";
        $user->groups[] = $g1;
        $user->groups[] = $g2;

        // Create
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $u1Id = $user->id;
        $g1Id = $g1->id;
        $g2Id = $g2->id;

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

}