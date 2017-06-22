<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\Address;
use Doctrine\Tests\Models\Quote\City;
use Doctrine\Tests\Models\Quote\FullAddress;
use Doctrine\Tests\Models\Quote\Group;
use Doctrine\Tests\Models\Quote\Phone;
use Doctrine\Tests\Models\Quote\User;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group 6402
 */
class GH6402Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('quote');

        parent::setUp();
    }

    public function testFind()
    {
        $id = $this->createAddress();

        $address = $this->em->find(Address::class, $id);

        self::assertNotNull($address->user);
    }

    public function testQuery()
    {
        $id = $this->createAddress();

        $addresses = $this->em->createQuery('SELECT a FROM ' . Address::class . ' a WHERE a.id = :id')
            ->setParameter('id', $id)
            ->getResult();

        self::assertCount(1, $addresses);
        self::assertNotNull($addresses[0]->user);
    }

    public function testFindWithSubClass()
    {
        $id = $this->createFullAddress();

        $address = $this->em->find(FullAddress::class, $id);

        self::assertNotNull($address->user);
    }

    public function testQueryWithSubClass()
    {
        $id = $this->createFullAddress();

        $addresses = $this->em->createQuery('SELECT a FROM ' . FullAddress::class . ' a WHERE a.id = :id')
            ->setParameter('id', $id)
            ->getResult();

        self::assertCount(1, $addresses);
        self::assertNotNull($addresses[0]->user);
    }

    private function createAddress()
    {
        $address = new Address();
        $address->zip = 'bar';

        $this->persistAddress($address);

        return $address->id;
    }

    private function createFullAddress()
    {
        $address = new FullAddress();
        $address->zip = 'bar';
        $address->city = new City('London');

        $this->persistAddress($address);

        return $address->id;
    }

    private function persistAddress(Address $address)
    {
        $user = new User();
        $user->name = "foo";
        $user->setAddress($address);

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();
    }
}
