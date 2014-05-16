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
        $this->_em->persist($this->foo);

        $this->merc = new Car();
        $this->merc->setBrand('Mercedes');
        $this->merc->setModel('C-Class');
        $this->_em->persist($this->merc);

        $this->_em->flush();

        $ride = new Ride($this->foo, $this->merc);
        $this->_em->persist($ride);

        $this->_em->flush();
    }

    public function testFindUsingAnArrayOfObjectAsPrimaryKey()
    {
        $ride1 = $this->_em->find('Doctrine\Tests\Models\Taxi\Ride', array(
            'driver' => $this->foo->getId(),
            'car'    => $this->merc->getBrand())
        );

        $this->assertInstanceOf('Doctrine\Tests\Models\Taxi\Ride', $ride1);

        $ride2 = $this->_em->find('Doctrine\Tests\Models\Taxi\Ride', array(
            'driver' => $this->foo,
            'car'    => $this->merc
        ));

        $this->assertInstanceOf('Doctrine\Tests\Models\Taxi\Ride', $ride2);
        $this->assertSame($ride1, $ride2);
    }
}
