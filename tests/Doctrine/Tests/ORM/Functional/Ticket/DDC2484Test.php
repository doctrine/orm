<?php
/**
 * This test represents different loading paths based on EAGER or LAZY loading of associations.
 *
 * This is a feature, lazy loading, which is not consistent because it does not send the lazy loaded
 * entity through the postLoad event.  Eager loading does fire postLoad when the association is not
 * already loaded.  Therefore when an entity is lazy loaded it does not follow the lifecycle events.
 *
 * @tag LazyLoading, EagerLoading, postLoad, LifecycleEvents
 */

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;

require_once __DIR__ . '/../../../TestInit.php';

class DDC2484Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() 
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Ticket\DDC2484_Car'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Ticket\DDC2484_EagerOwner'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Ticket\DDC2484_LazyOwner'),
            ));
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    /**
     * @group DDC-2484
     */
    public function test()
    {
        new OrmEvents($this->_em->getEventManager());

        $car = new DDC2484_Car;
        $car->setBrand('Volkswagen');

        $eagerOwner = new DDC2484_EagerOwner;
        $eagerOwner->setCar($car);

        $lazyOwner = new DDC2484_LazyOwner;
        $lazyOwner->setCar($car);

        $this->_em->persist($car);
        $this->_em->persist($eagerOwner);
        $this->_em->persist($lazyOwner);

        $this->_em->flush();

        $carId = $car->getId();
        $eagerOwnerId = $eagerOwner->getId();
        $lazyOwnerId = $lazyOwner->getId();

        $this->_em->clear();
        unset($car, $lazyOwner, $eagerOwner);

        $car = $this->_em->getRepository('Doctrine\Tests\ORM\Functional\Ticket\DDC2484_Car')->find($carId);
        $this->assertEquals('BMW', $car->getBrand());

        $this->_em->clear();
        unset($car);

        // Eager Loading triggers postLoad
        $eagerDriver = $this->_em->getRepository('Doctrine\Tests\ORM\Functional\Ticket\DDC2484_EagerOwner')->find($eagerOwnerId);
        $this->assertEquals('BMW', $eagerDriver->getCar()->getBrand());

        // Lazy Loading show entity already loaded through postLoad
        $lazyDriver = $this->_em->getRepository('Doctrine\Tests\ORM\Functional\Ticket\DDC2484_LazyOwner')->find($lazyOwnerId);
        $this->assertEquals('BMW', $lazyDriver->getCar()->getBrand());

        $this->_em->clear();
        unset($eagerDriver, $lazyDriver);

        // Lazy Loading does not trigger postLoad
        $lazyDriver = $this->_em->getRepository('Doctrine\Tests\ORM\Functional\Ticket\DDC2484_LazyOwner')->find($lazyOwnerId);
        $this->assertEquals('Volkswagen', $lazyDriver->getCar()->getBrand()); // Should be BMW

        // Eager Loading does not trigger postLoad for loaded entity
        $eagerDriver = $this->_em->getRepository('Doctrine\Tests\ORM\Functional\Ticket\DDC2484_EagerOwner')->find($eagerOwnerId);
        $this->assertEquals('Volkswagen', $eagerDriver->getCar()->getBrand());  // Should be BMW

        $this->_em->clear();
        unset($eagerDriver, $lazyDriver);

        // Eager Loading works triggering postLoad
        $eagerDriver = $this->_em->getRepository('Doctrine\Tests\ORM\Functional\Ticket\DDC2484_EagerOwner')->find($eagerOwnerId);
        $this->assertEquals('BMW', $eagerDriver->getCar()->getBrand());

        $this->_em->clear();
        unset($eagerDriver);

        // Lazy Loading does not trigger postLoad
        $lazyDriver = $this->_em->getRepository('Doctrine\Tests\ORM\Functional\Ticket\DDC2484_LazyOwner')->find($lazyOwnerId);
        $this->assertEquals('Volkswagen', $lazyDriver->getCar()->getBrand());  // Should be BMW
    }
}

class ORMEvents 
{
    public function __construct($evm) 
    {
        $evm->addEventListener(array(
            EVENTS::postLoad,
        ), $this);
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        if (get_class($args->getEntity()) == 'Doctrine\Tests\ORM\Functional\Ticket\DDC2484_Car') {
            // Change company fleet to BMW
            $args->getEntity()->setBrand('BMW');
        }
    }
}

/**
 * @Entity
 * @Table(name="cars")
 */
class DDC2484_Car
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="string", length=50)
     */
    private $brand;

    public function getId()
    {
        return $this->id;
    }

    public function getBrand()
    {
        return $this->brand;
    }

    public function setBrand($value)
    {
        $this->brand = $value;
        return $this;
    }
}

/**
 * @Entity
 * @Table(name="lazy_owners")
 */
class DDC2484_LazyOwner
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @OneToOne(targetEntity="DDC2484_Car", cascade={"persist"}, fetch="LAZY")
     * @JoinColumn(name="car_id", referencedColumnName="id")
     */
    private $car;

    public function getId()
    {
        return $this->id;
    }

    public function getCar() 
    {
        return $this->car;
    }

    public function setCar(DDC2484_Car $car) 
    {
        $this->car = $car;
    }
}

/**
 * @Entity
 * @Table(name="eager_owners")
 */
class DDC2484_EagerOwner
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @OneToOne(targetEntity="DDC2484_Car", cascade={"persist"}, fetch="EAGER")
     * @JoinColumn(name="car_id", referencedColumnName="id")
     */
    private $car;

    public function getId()
    {
        return $this->id;
    }


    public function getCar() 
    {
        return $this->car;
    }

    public function setCar(DDC2484_Car $car) 
    {
        $this->car = $car;
    }
}
