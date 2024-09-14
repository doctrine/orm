<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Taxi\Car;
use Doctrine\Tests\Models\Taxi\Driver;
use Doctrine\Tests\Models\Taxi\Ride;
use Doctrine\Tests\OrmFunctionalTestCase;

/** @group DDC-3068 */
class DDC3068Test extends OrmFunctionalTestCase
{
    /** @var Driver */
    private $foo;

    /** @var Car */
    private $merc;

    protected function setUp(): void
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

    public function testFindUsingAnArrayOfObjectAsPrimaryKey(): void
    {
        $ride1 = $this->_em->find(Ride::class, [
            'driver' => $this->foo->getId(),
            'car'    => $this->merc->getBrand(),
        ]);

        self::assertInstanceOf(Ride::class, $ride1);

        $ride2 = $this->_em->find(Ride::class, [
            'driver' => $this->foo,
            'car'    => $this->merc,
        ]);

        self::assertInstanceOf(Ride::class, $ride2);
        self::assertSame($ride1, $ride2);
    }
}
