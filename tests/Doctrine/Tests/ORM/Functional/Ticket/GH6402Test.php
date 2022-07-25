<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\Address;
use Doctrine\Tests\Models\Quote\City;
use Doctrine\Tests\Models\Quote\FullAddress;
use Doctrine\Tests\Models\Quote\User;
use Doctrine\Tests\OrmFunctionalTestCase;

/** @group GH-6402 */
class GH6402Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('quote');

        parent::setUp();
    }

    public function testFind(): void
    {
        $id = $this->createAddress();

        $address = $this->_em->find(Address::class, $id);
        self::assertNotNull($address->user);
    }

    public function testQuery(): void
    {
        $id = $this->createAddress();

        $addresses = $this->_em->createQuery('SELECT a FROM ' . Address::class . ' a WHERE a.id = :id')
            ->setParameter('id', $id)
            ->getResult();

        self::assertCount(1, $addresses);
        self::assertNotNull($addresses[0]->user);
    }

    public function testFindWithSubClass(): void
    {
        $id = $this->createFullAddress();

        $address = $this->_em->find(FullAddress::class, $id);
        self::assertNotNull($address->user);
    }

    public function testQueryWithSubClass(): void
    {
        $id = $this->createFullAddress();

        $addresses = $this->_em->createQuery('SELECT a FROM ' . FullAddress::class . ' a WHERE a.id = :id')
            ->setParameter('id', $id)
            ->getResult();

        self::assertCount(1, $addresses);
        self::assertNotNull($addresses[0]->user);
    }

    private function createAddress(): int
    {
        $address      = new Address();
        $address->zip = 'bar';

        $this->persistAddress($address);

        return $address->id;
    }

    private function createFullAddress(): int
    {
        $address       = new FullAddress();
        $address->zip  = 'bar';
        $address->city = new City('London');

        $this->persistAddress($address);

        return $address->id;
    }

    private function persistAddress(Address $address): void
    {
        $user       = new User();
        $user->name = 'foo';
        $user->setAddress($address);

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();
    }
}
