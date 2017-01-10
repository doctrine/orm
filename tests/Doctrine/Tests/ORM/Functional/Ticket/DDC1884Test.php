<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Taxi\Car;
use Doctrine\Tests\Models\Taxi\Driver;
use Doctrine\Tests\Models\Taxi\Ride;
use Doctrine\Tests\Models\Taxi\PaidRide;

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

        list($bimmer, $crysler, $merc, $volvo) = $this->createCars(Car::class);
        list($john, $foo) = $this->createDrivers(Driver::class);
        $this->em->flush();

        $ride1 = new Ride($john, $bimmer);
        $ride2 = new Ride($john, $merc);
        $ride3 = new Ride($john, $volvo);
        $ride4 = new Ride($foo, $merc);

        $this->em->persist($ride1);
        $this->em->persist($ride2);
        $this->em->persist($ride3);
        $this->em->persist($ride4);

        $ride5 = new PaidRide($john, $bimmer);
        $ride5->setFare(10.50);

        $ride6 = new PaidRide($john, $merc);
        $ride6->setFare(16.00);

        $ride7 = new PaidRide($john, $volvo);
        $ride7->setFare(20.70);

        $ride8 = new PaidRide($foo, $merc);
        $ride8->setFare(32.15);

        $this->em->persist($ride5);
        $this->em->persist($ride6);
        $this->em->persist($ride7);
        $this->em->persist($ride8);

        $this->em->flush();
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

        $this->em->persist($bimmer);
        $this->em->persist($crysler);
        $this->em->persist($merc);
        $this->em->persist($volvo);

        return [$bimmer, $crysler, $merc, $volvo];
    }

    private function createDrivers($class)
    {
        $john = new $class;
        $john->setName('John Doe');

        $foo = new $class;
        $foo->setName('Foo Bar');

        $this->em->persist($foo);
        $this->em->persist($john);

        return [$john, $foo];
    }

    /**
     * 1) Ride contains only columns that are part of its composite primary key
     * 2) We use fetch joins here
     */
    public function testSelectFromInverseSideWithCompositePkAndSolelyIdentifierColumnsUsingFetchJoins()
    {
        $qb = $this->em->createQueryBuilder();

        $result = $qb->select('d, dr, c')
            ->from(Driver::class, 'd')
            ->leftJoin('d.freeDriverRides', 'dr')
            ->leftJoin('dr.car', 'c')
            ->where('d.name = ?1')
            ->setParameter(1, 'John Doe')
            ->getQuery()
            ->getArrayResult();

        self::assertCount(1, $result);
        self::assertArrayHasKey('freeDriverRides', $result[0]);
        self::assertCount(3, $result[0]['freeDriverRides']);
    }

    /**
     * 1) PaidRide contains an extra column that is not part of the composite primary key
     * 2) Again we will use fetch joins
     */
    public function testSelectFromInverseSideWithCompositePkUsingFetchJoins()
    {
        $qb = $this->em->createQueryBuilder();

        $result = $qb->select('d, dr, c')
            ->from(Driver::class, 'd')
            ->leftJoin('d.driverRides', 'dr')
            ->leftJoin('dr.car', 'c')
            ->where('d.name = ?1')
            ->setParameter(1, 'John Doe')
            ->getQuery()->getArrayResult();

        self::assertCount(1, $result);
        self::assertArrayHasKey('driverRides', $result[0]);
        self::assertCount(3, $result[0]['driverRides']);
    }

    /**
     * The other way around will fail too
     */
    public function testSelectFromOwningSideUsingFetchJoins()
    {
        $qb = $this->em->createQueryBuilder();

        $result =  $qb->select('r, d, c')
            ->from(PaidRide::class, 'r')
            ->leftJoin('r.driver', 'd')
            ->leftJoin('r.car', 'c')
            ->where('d.name = ?1')
            ->setParameter(1, 'John Doe')
            ->getQuery()->getArrayResult();

        self::assertCount(3, $result);
        self::assertArrayHasKey('driver', $result[0]);
        self::assertArrayHasKey('car', $result[0]);
    }
}
