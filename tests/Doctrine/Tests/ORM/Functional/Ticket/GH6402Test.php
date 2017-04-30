<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\Address;
use Doctrine\Tests\Models\Quote\Group;
use Doctrine\Tests\Models\Quote\Phone;
use Doctrine\Tests\Models\Quote\User;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-6402
 */
class GH6402Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->setUpEntitySchema([
                Address::class,
                Group::class,
                Phone::class,
                User::class,
            ]);
        } catch (\Exception $exception) {
        }
    }

    public function testFind()
    {
        $id = $this->createAddress();

        $address = $this->_em->find(Address::class, $id);
        self::assertNotNull($address->user);
    }

    public function testQuery()
    {
        $id = $this->createAddress();

        $addresses = $this->_em->createQuery("SELECT a FROM " . Address::class . " a WHERE a.id = :id")
            ->setParameter("id", $id)
            ->getResult();

        self::assertCount(1, $addresses);
        self::assertNotNull($addresses[0]->user);
    }

    private function createAddress()
    {
        $user = new User();
        $user->name = "foo";

        $address = new Address();
        $address->zip = "bar";
        $user->setAddress($address);

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        return $address->id;
    }
}
