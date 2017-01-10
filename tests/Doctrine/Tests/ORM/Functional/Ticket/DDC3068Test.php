<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Taxi\Car;
use Doctrine\Tests\Models\Taxi\Driver;
use Doctrine\Tests\Models\Taxi\Ride;

/**
 * @group DDC-3068
 *
 * @author Giorgio Premi <giosh94mhz@gmail.com>
 */
class DDC3068Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $foo;
    private $merc;

    protected function setUp()
    {
        $this->useModelSet('taxi');
        parent::setUp();

        $this->foo = new Driver();
        $this->foo->setName('Foo Bar');
        $this->em->persist($this->foo);

        $this->merc = new Car();
        $this->merc->setBrand('Mercedes');
        $this->merc->setModel('C-Class');
        $this->em->persist($this->merc);

        $this->em->flush();

        $ride = new Ride($this->foo, $this->merc);
        $this->em->persist($ride);

        $this->em->flush();
    }

    public function testFindUsingAnArrayOfObjectAsPrimaryKey()
    {
        $ride1 = $this->em->find(Ride::class, [
            'driver' => $this->foo->getId(),
            'car'    => $this->merc->getBrand()
            ]
        );

        self::assertInstanceOf(Ride::class, $ride1);

        $ride2 = $this->em->find(Ride::class, [
            'driver' => $this->foo,
            'car'    => $this->merc
        ]
        );

        self::assertInstanceOf(Ride::class, $ride2);
        self::assertSame($ride1, $ride2);
    }
}
