<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\User;
use Doctrine\Tests\Models\Quote\Address;

/**
 * @group DDC-1845
 * @group DDC-142
 */
class DDC142Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        $this->useModelSet('quote');

        parent::setUp();
    }

    public function testCreateRetrieveUpdateDelete()
    {

        $user           = new User;
        $user->name     = 'FabioBatSilva';
        $this->_em->persist($user);

        $address        = new Address;
        $address->zip   = '12345';
        $this->_em->persist($address);

        $this->_em->flush();

        $addressRef = $this->_em->getReference(Address::class, $address->getId());

        $user->setAddress($addressRef);

        $this->_em->flush();
        $this->_em->clear();

        $id = $user->id;
        $this->assertNotNull($id);


        $user       = $this->_em->find(User::class, $id);
        $address    = $user->getAddress();

        $this->assertInstanceOf(User::class, $user);
        $this->assertInstanceOf(Address::class, $user->getAddress());

        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals('12345', $address->zip);


        $user->name     = 'FabioBatSilva1';
        $user->address  = null;

        $this->_em->persist($user);
        $this->_em->remove($address);
        $this->_em->flush();
        $this->_em->clear();


        $user = $this->_em->find(User::class, $id);
        $this->assertInstanceOf(User::class, $user);
        $this->assertNull($user->getAddress());

        $this->assertEquals('FabioBatSilva1', $user->name);


        $this->_em->remove($user);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertNull($this->_em->find(User::class, $id));
    }

}
