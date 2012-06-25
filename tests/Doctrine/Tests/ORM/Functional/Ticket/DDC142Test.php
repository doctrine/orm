<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\User;
use Doctrine\Tests\Models\Quote\Address;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1845
 * @group DDC-142
 */
class DDC142Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\User'),
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\Group'),
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\Phone'),
                $this->_em->getClassMetadata('Doctrine\Tests\Models\Quote\Address'),
            ));
        } catch(\Exception $e) {
        }
    }

    public function testCreateRetreaveUpdateDelete()
    {

        $user           = new User;
        $user->name     = 'FabioBatSilva';
        $this->_em->persist($user);

        $address        = new Address;
        $address->zip   = '12345';
        $this->_em->persist($address);

        $this->_em->flush();

        $addressRef = $this->_em->getReference('Doctrine\Tests\Models\Quote\Address', $address->getId());

        $user->setAddress($addressRef);

        $this->_em->flush();
        $this->_em->clear();

        $id = $user->id;
        $this->assertNotNull($id);

        
        $user       = $this->_em->find('Doctrine\Tests\Models\Quote\User', $id);
        $address    = $user->getAddress();

        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\User', $user);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\Address', $user->getAddress());

        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals('12345', $address->zip);


        $user->name     = 'FabioBatSilva1';
        $user->address  = null;

        $this->_em->persist($user);
        $this->_em->remove($address);
        $this->_em->flush();
        $this->_em->clear();


        $user = $this->_em->find('Doctrine\Tests\Models\Quote\User', $id);
        $this->assertInstanceOf('Doctrine\Tests\Models\Quote\User', $user);
        $this->assertNull($user->getAddress());

        $this->assertEquals('FabioBatSilva1', $user->name);
        
        
        $this->_em->remove($user);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertNull($this->_em->find('Doctrine\Tests\Models\Quote\User', $id));
    }

}