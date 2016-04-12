<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group GH-5762
 */
class GH5762Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setup()
    {
        parent::setup();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\GH5762Driver'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\GH5762DriverRide'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\GH5762Car'),
        ));
    }

    public function testIssue()
    {
        $result = $this->fetchData();

        $this->assertInstanceOf(__NAMESPACE__ . '\GH5762Driver', $result);
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $result->getDriverRides());
        $this->assertInstanceOf(__NAMESPACE__ . '\GH5762DriverRide', $result->getDriverRides()->get(0));
        $this->assertInstanceOf(__NAMESPACE__ . '\GH5762Car', $result->getDriverRides()->get(0)->getCar());

        $cars = array();
        foreach ($result->getDriverRides() as $ride) {
            $cars[] = $ride->getCar()->getBrand();
        }
        $this->assertEquals(count($cars), count(array_unique($cars)));

        $this->assertContains('BMW', $cars);
        $this->assertContains('Crysler', $cars);
        $this->assertContains('Dodge', $cars);
        $this->assertContains('Mercedes', $cars);
        $this->assertContains('Volvo', $cars);
    }

    private function fetchData()
    {
        $this->createData();

        $qb = $this->_em->createQueryBuilder();
        $qb->select('d, dr, c')
            ->from(__NAMESPACE__ . '\GH5762Driver', 'd')
            ->leftJoin('d.driverRides', 'dr')
            ->leftJoin('dr.car', 'c')
            ->where('d.id = 1');

        return $qb->getQuery()->getSingleResult();
    }

    private function createData()
    {
        $car1 = new GH5762Car('BMW', '7 Series');
        $car2 = new GH5762Car('Crysler', '300');
        $car3 = new GH5762Car('Dodge', 'Dart');
        $car4 = new GH5762Car('Mercedes', 'C-Class');
        $car5 = new GH5762Car('Volvo', 'XC90');

        $driver = new GH5762Driver(1, 'John Doe');

        $ride1 = new GH5762DriverRide($driver, $car1);
        $ride2 = new GH5762DriverRide($driver, $car2);
        $ride3 = new GH5762DriverRide($driver, $car3);
        $ride4 = new GH5762DriverRide($driver, $car4);
        $ride5 = new GH5762DriverRide($driver, $car5);

        $this->_em->persist($car1);
        $this->_em->persist($car2);
        $this->_em->persist($car3);
        $this->_em->persist($car4);
        $this->_em->persist($car5);

        $this->_em->persist($driver);

        $this->_em->persist($ride1);
        $this->_em->persist($ride2);
        $this->_em->persist($ride3);
        $this->_em->persist($ride4);
        $this->_em->persist($ride5);

        $this->_em->flush();
        $this->_em->clear();
    }
}

/**
 * @Entity 
 * @Table(name="driver")
 */
class GH5762Driver
{

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @Column(type="string", length=255);
     */
    private $name;

    /**
     * @OneToMany(targetEntity="GH5762DriverRide", mappedBy="driver")
     */
    private $driverRides;

    function __construct($id, $name)
    {
        $this->driverRides = new ArrayCollection();
        $this->id = $id;
        $this->name = $name;
    }

    function getId()
    {
        return $this->id;
    }

    function getName()
    {
        return $this->name;
    }

    function getDriverRides()
    {
        return $this->driverRides;
    }
}

/**
 * @Entity
 * @Table(name="driver_ride")
 */
class GH5762DriverRide
{

    /**
     * @Id
     * @ManyToOne(targetEntity="GH5762Driver", inversedBy="driverRides")
     * @JoinColumn(name="driver_id", referencedColumnName="id")
     */
    private $driver;

    /**
     * @Id
     * @ManyToOne(targetEntity="GH5762Car", inversedBy="carRides")
     * @JoinColumn(name="car", referencedColumnName="brand")
     */
    private $car;

    function __construct(GH5762Driver $driver, GH5762Car $car)
    {
        $this->driver = $driver;
        $this->car = $car;

        $this->driver->getDriverRides()->add($this);
        $this->car->getCarRides()->add($this);
    }

    function getDriver()
    {
        return $this->driver;
    }

    function getCar()
    {
        return $this->car;
    }
}

/**
 * @Entity
 * @Table(name="car")
 */
class GH5762Car
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
     * @OneToMany(targetEntity="GH5762DriverRide", mappedBy="car")
     */
    private $carRides;

    function __construct($brand, $model)
    {
        $this->carRides = new ArrayCollection();
        $this->brand = $brand;
        $this->model = $model;
    }

    function getBrand()
    {
        return $this->brand;
    }

    function getModel()
    {
        return $this->model;
    }

    function getCarRides()
    {
        return $this->carRides;
    }
}
