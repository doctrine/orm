<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\Group;
use Doctrine\Tests\Models\Quote\Phone;
use Doctrine\Tests\Models\Quote\User;
use Doctrine\Tests\Models\Quote\Address;

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
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(User::class),
                $this->em->getClassMetadata(Group::class),
                $this->em->getClassMetadata(Phone::class),
                $this->em->getClassMetadata(Address::class),
                ]
            );
        } catch(\Exception $e) {
        }
    }

    public function testCreateRetrieveUpdateDelete()
    {

        $user           = new User;
        $user->name     = 'FabioBatSilva';
        $this->em->persist($user);

        $address        = new Address;
        $address->zip   = '12345';
        $this->em->persist($address);

        $this->em->flush();

        $addressRef = $this->em->getReference(Address::class, $address->getId());

        $user->setAddress($addressRef);

        $this->em->flush();
        $this->em->clear();

        $id = $user->id;
        self::assertNotNull($id);


        $user       = $this->em->find(User::class, $id);
        $address    = $user->getAddress();

        self::assertInstanceOf(User::class, $user);
        self::assertInstanceOf(Address::class, $user->getAddress());

        self::assertEquals('FabioBatSilva', $user->name);
        self::assertEquals('12345', $address->zip);


        $user->name     = 'FabioBatSilva1';
        $user->address  = null;

        $this->em->persist($user);
        $this->em->remove($address);
        $this->em->flush();
        $this->em->clear();


        $user = $this->em->find(User::class, $id);
        self::assertInstanceOf(User::class, $user);
        self::assertNull($user->getAddress());

        self::assertEquals('FabioBatSilva1', $user->name);

        $this->em->remove($user);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->find(User::class, $id));
    }

}
