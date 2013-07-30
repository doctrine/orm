<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Taxi\Car,
    Doctrine\Tests\Models\Taxi\Driver,
    Doctrine\Tests\Models\Taxi\Ride,
    Doctrine\Tests\Models\Taxi\PaidRide;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1884
 * @author Sander Coolen <sander@jibber.nl>
 */
class DDC1884Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('taxi');
        parent::setUp();

        list($bimmer, $crysler, $merc, $volvo) = $this->createCars('Doctrine\Tests\Models\Taxi\Car');
        list($john, $foo) = $this->createDrivers('Doctrine\Tests\Models\Taxi\Driver');
        $this->_em->flush();

        $ride1 = new Ride($john, $bimmer);
        $ride2 = new Ride($john, $merc);
        $ride3 = new Ride($john, $volvo);
        $ride4 = new Ride($foo, $merc);

        $this->_em->persist($ride1);
        $this->_em->persist($ride2);
        $this->_em->persist($ride3);
        $this->_em->persist($ride4);

        $ride5 = new PaidRide($john, $bimmer);
        $ride5->setFare(10.50);

        $ride6 = new PaidRide($john, $merc);
        $ride6->setFare(16.00);

        $ride7 = new PaidRide($john, $volvo);
        $ride7->setFare(20.70);

        $ride8 = new PaidRide($foo, $merc);
        $ride8->setFare(32.15);

        $this->_em->persist($ride5);
        $this->_em->persist($ride6);
        $this->_em->persist($ride7);
        $this->_em->persist($ride8);

        $this->_em->flush();
    }

    private function createCars($class)
    {
        $bimmer = new $class;
        $bimmer->setBrand('BMW');
        $bimmer->setModel('7-Series');

        $crysler = new $class;
        $crysler->setBrand('Crysler');
        $crysler->setModel('300');

        $merc = new $class;
        $merc->setBrand('Mercedes');
        $merc->setModel('C-Class');

        $volvo = new $class;
        $volvo->setBrand('Volvo');
        $volvo->setModel('XC90');

        $this->_em->persist($bimmer);
        $this->_em->persist($crysler);
        $this->_em->persist($merc);
        $this->_em->persist($volvo);

        return array($bimmer, $crysler, $merc, $volvo);
    }

    private function createDrivers($class)
    {
        $john = new $class;
        $john->setName('John Doe');

        $foo = new $class;
        $foo->setName('Foo Bar');

        $this->_em->persist($foo);
        $this->_em->persist($john);

        return array($john, $foo);
    }

    /**
     * 1) Ride contains only columns that are part of its composite primary key
     * 2) We use fetch joins here
     */
    public function testSelectFromInverseSideWithCompositePkAndSolelyIdentifierColumnsUsingFetchJoins()
    {
        $qb = $this->_em->createQueryBuilder();

        $result = $qb->select('d, dr, c')
            ->from('Doctrine\Tests\Models\Taxi\Driver', 'd')
            ->leftJoin('d.freeDriverRides', 'dr')
            ->leftJoin('dr.car', 'c')
            ->where('d.name = ?1')
            ->setParameter(1, 'John Doe')
            ->getQuery()
            ->getArrayResult();

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('freeDriverRides', $result[0]);
        $this->assertCount(3, $result[0]['freeDriverRides']);
    }

    /**
     * 1) PaidRide contains an extra column that is not part of the composite primary key
     * 2) Again we will use fetch joins
     */
    public function testSelectFromInverseSideWithCompositePkUsingFetchJoins()
    {
        $qb = $this->_em->createQueryBuilder();

        $result = $qb->select('d, dr, c')
            ->from('Doctrine\Tests\Models\Taxi\Driver', 'd')
            ->leftJoin('d.driverRides', 'dr')
            ->leftJoin('dr.car', 'c')
            ->where('d.name = ?1')
            ->setParameter(1, 'John Doe')
            ->getQuery()->getArrayResult();

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('driverRides', $result[0]);
        $this->assertCount(3, $result[0]['driverRides']);
    }

    /**
     * The other way around will fail too
     */
    public function testSelectFromOwningSideUsingFetchJoins()
    {
        $qb = $this->_em->createQueryBuilder();

        $result =  $qb->select('r, d, c')
            ->from('Doctrine\Tests\Models\Taxi\PaidRide', 'r')
            ->leftJoin('r.driver', 'd')
            ->leftJoin('r.car', 'c')
            ->where('d.name = ?1')
            ->setParameter(1, 'John Doe')
            ->getQuery()->getArrayResult();

        $this->assertCount(3, $result);
        $this->assertArrayHasKey('driver', $result[0]);
        $this->assertArrayHasKey('car', $result[0]);
    }
}
