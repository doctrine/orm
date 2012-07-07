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
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\RideWithSurrogatePk'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1884Driver'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1884Car')
            ));
        } catch (\Exception $e) {
            
        }
        
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
        
        list($bimmer, $crysler, $merc, $volvo) = $this->createCars(__NAMESPACE__ . '\DDC1884Car');
        list($john, $foo) = $this->createDrivers(__NAMESPACE__ . '\DDC1884Driver');
        
        $ride9  = new RideWithSurrogatePk();
        $ride9->setDriver($john);
        $ride9->setCar($crysler);
        $ride10 = new RideWithSurrogatePk();
        $ride10->setDriver($john);
        $ride10->setCar($merc);
        $ride11 = new RideWithSurrogatePk();
        $ride11->setDriver($john);
        $ride11->setCar($volvo);
        $ride12 = new RideWithSurrogatePk();
        $ride12->setDriver($foo);
        $ride12->setCar($bimmer);
        
        $this->_em->persist($ride9);
        $this->_em->persist($ride10);
        $this->_em->persist($ride11);
        $this->_em->persist($ride12);
        
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
    
    protected function tearDown()
    {
        self::$_sharedConn->executeUpdate('DELETE FROM taxi_ride_with_surrogate_pk');
        self::$_sharedConn->executeUpdate('DELETE FROM ddc1884_taxi_driver');
        self::$_sharedConn->executeUpdate('DELETE FROM ddc1884_taxi_car');
        parent::tearDown();
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
                     ->getQuery()->getArrayResult();
        
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
    
    /**
     * Just a test to show that it works as expected using a surrogate primary key 
     */
    public function testSelectFromInverseSideWithSurrogatePkUsingFetchJoins()
    {
        $qb = $this->_em->createQueryBuilder();

        $result = $qb->select('d, dr, c')
                     ->from('Doctrine\Tests\ORM\Functional\Ticket\DDC1884Driver', 'd')
                     ->leftJoin('d.driverRides', 'dr')
                     ->leftJoin('dr.car', 'c')
                     ->where('d.name = ?1')
                     ->setParameter(1, 'John Doe')
                     ->getQuery()->getArrayResult();
        
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('driverRides', $result[0]);
        $this->assertCount(3, $result[0]['driverRides']);
    }
    
    public function testSelectFromOwningSideUsingFetchJoinsAndSurrogatePk()
    {
        $qb = $this->_em->createQueryBuilder();

        $result =  $qb->select('r, d, c')
                      ->from('Doctrine\Tests\ORM\Functional\Ticket\RideWithSurrogatePk', 'r')
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

/*
 * Model classes below are copies of the ones in Doctrine\Tests\Models\Taxi
 * except Ride below uses an surrogate primary key
 */

/**
 * @Entity
 * @Table(name="taxi_ride_with_surrogate_pk")
 */
class RideWithSurrogatePk
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @ManyToOne(targetEntity="DDC1884Driver", inversedBy="_driverRides")
     * @JoinColumn(name="driver_id", referencedColumnName="id")
     */
    private $driver;
    
    /**
     * @ManyToOne(targetEntity="DDC1884Car", inversedBy="_carRides")
     * @JoinColumn(name="car", referencedColumnName="brand")
     */
    private $car;
    
    public function setDriver(DDC1884Driver $driver)
    {
        $this->driver = $driver;
    }
    
    public function setCar(DDC1884Car $car)
    {
        $this->car = $car;
    }
}

/**
 * @Entity
 * @Table(name="ddc1884_taxi_driver")
 */
class DDC1884Driver
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @Column(type="string", length=255);
     */
    private $name;
    
    /**
     * @OneToMany(targetEntity="RideWithSurrogatePk", mappedBy="driver")
     */
    private $driverRides;
    
    public function setName($name)
    {
        $this->name = $name;
    }
}

/**
 * @Entity
 * @Table(name="ddc1884_taxi_car")
 */
class DDC1884Car
{
    /**
     * @Id
     * @Column(type="string", length=25)
     * @GeneratedValue(strategy="NONE")
     */
    private $brand;
    
    /**
     * @Column(type="string", length=255);
     */
    private $model;
    
    /**
     * @OneToMany(targetEntity="RideWithSurrogatePk", mappedBy="car")
     */
    private $carRides;
    
    public function setBrand($brand)
    {
        $this->brand = $brand;
    }
    
    public function setModel($model)
    {
        $this->model = $model;
    }
}