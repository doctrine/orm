<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\Models\Company\CompanyCar;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

require_once __DIR__ . '/../../../TestInit.php';

class DDC2484Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp() {
        $this->useModelSet('company');
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @group DDC-2484
     */
    public function test()
    {
        new OrmEvents($this->_em->getEventManager());

        $car = new CompanyCar('BMW');

        $manager = new CompanyManager;
        $manager->setName("Foo");
        $manager->setTitle('BMW Driver');
        $manager->setCar($car);
        $manager->setSalary(103621); #M6
        $manager->setDepartment('IT');

        $this->_em->persist($manager);
        $this->_em->persist($car);

        $this->_em->flush();

        $managerId = $manager->getId();
        $carId = $car->getId();

        $this->_em->clear();

        $driverlessCar = $this->_em->getRepository('Doctrine\Tests\Models\Company\CompanyCar')->find($carId);
        $this->assertEquals('Volkswagen', $driverlessCar->getBrand());

        $this->_em->clear();

        $manager = $this->_em->getRepository('Doctrine\Tests\Models\Company\CompanyManager')->find($managerId);
        $this->assertEquals('Volkswagen', $manager->getCar()->getBrand());
    }
}

class ORMEvents {
    public function __construct($evm) {
        $evm->addEventListener(array(
            EVENTS::postLoad,
        ), $this);
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        if (get_class($args->getEntity()) == 'Doctrine\Tests\Models\Company\CompanyCar') {
            $args->getEntity()->setBrand('Volkswagen');
        }

    }
}