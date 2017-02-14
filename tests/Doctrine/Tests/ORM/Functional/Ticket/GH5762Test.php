<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-5762
 */
class GH5762Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(GH5762Driver::class),
            $this->em->getClassMetadata(GH5762DriverRide::class),
            $this->em->getClassMetadata(GH5762Car::class),
            ]
        );
    }

    public function testIssue()
    {
        $result = $this->fetchData();

        self::assertInstanceOf(GH5762Driver::class, $result);
        self::assertInstanceOf(PersistentCollection::class, $result->driverRides);
        self::assertInstanceOf(GH5762DriverRide::class, $result->driverRides->get(0));
        self::assertInstanceOf(GH5762Car::class, $result->driverRides->get(0)->car);

        $cars = [];
        foreach ($result->driverRides as $ride) {
            $cars[] = $ride->car->brand;
        }

        self::assertEquals(count($cars), count(array_unique($cars)));

        self::assertContains('BMW', $cars);
        self::assertContains('Crysler', $cars);
        self::assertContains('Dodge', $cars);
        self::assertContains('Mercedes', $cars);
        self::assertContains('Volvo', $cars);
    }

    private function fetchData()
    {
        $this->createData();

        $qb = $this->em->createQueryBuilder();
        $qb->select('d, dr, c')
            ->from(GH5762Driver::class, 'd')
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

        $this->em->persist($car1);
        $this->em->persist($car2);
        $this->em->persist($car3);
        $this->em->persist($car4);
        $this->em->persist($car5);

        $this->em->persist($driver);

        $this->em->persist($ride1);
        $this->em->persist($ride2);
        $this->em->persist($ride3);
        $this->em->persist($ride4);
        $this->em->persist($ride5);

        $this->em->flush();
        $this->em->clear();
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="driver")
 */
class GH5762Driver
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=255);
     */
    public $name;

    /**
     * @ORM\OneToMany(targetEntity="GH5762DriverRide", mappedBy="driver")
     */
    public $driverRides;

    public function __construct($id, $name)
    {
        $this->driverRides = new ArrayCollection();
        $this->id = $id;
        $this->name = $name;
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="driver_ride")
 */
class GH5762DriverRide
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="GH5762Driver", inversedBy="driverRides")
     * @ORM\JoinColumn(name="driver_id", referencedColumnName="id")
     */
    public $driver;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="GH5762Car", inversedBy="carRides")
     * @ORM\JoinColumn(name="car", referencedColumnName="brand")
     */
    public $car;

    function __construct(GH5762Driver $driver, GH5762Car $car)
    {
        $this->driver = $driver;
        $this->car = $car;

        $this->driver->driverRides->add($this);
        $this->car->carRides->add($this);
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="car")
 */
class GH5762Car
{

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=25)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $brand;

    /**
     * @ORM\Column(type="string", length=255);
     */
    public $model;

    /**
     * @ORM\OneToMany(targetEntity="GH5762DriverRide", mappedBy="car")
     */
    public $carRides;

    public function __construct($brand, $model)
    {
        $this->carRides = new ArrayCollection();
        $this->brand = $brand;
        $this->model = $model;
    }
}
