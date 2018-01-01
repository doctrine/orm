<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com> 
 */

namespace Doctrine\Tests\ORM\Functional\Ticket;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC2363Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(DDC2363Order::class),
            $this->_em->getClassMetadata(DDC2363ServicesPackage::class),
            $this->_em->getClassMetadata(DDC2363Service::class),
        ]);
    }

    public function testIssue()
    {
        // We create an Order with related ServicesPackage that is related to two Service.
        $order = new DDC2363Order();
        $order->name = 'My ORDER';

        $servicesPackage = $this->createNewServicesPackage();
        $order->services_package = $servicesPackage;

        $this->_em->persist($order);
        $this->_em->flush();

        // So, we assert that there is only one ServicesPackage and 2 PurchasedService.
        // On creation there is no problem, these assertions are green.
        $this->assertCount(1, $this->_em->getRepository(DDC2363ServicesPackage::class)->findAll());
        $this->assertCount(2, $this->_em->getRepository(DDC2363Service::class)->findAll());

        // Reinitializing EntityManager to simulate, for example, another HTTP request.
        $this->_em->clear();

        // Now we load previously persisted Order and associate it with a new ServicesPackage with two new Services.
        /** @var $order DDC2363Order */
        $order = $this->_em->getRepository(DDC2363Order::class)->find(1);

        self::assertInstanceOf(DDC2363Order::class, $order);

        $servicesPackage = $this->createNewServicesPackage();
        $order->services_package = $servicesPackage;

        $this->_em->persist($order);
        $this->_em->flush();

        // We assert again that there is only one ServicesPackage and two Services. This is because we have
        // orphanRemoval on Order::purchased_services_package and cascade="remove" on
        // PurchasedServicesPackage::services.
        $this->assertCount(1, $this->_em->getRepository(DDC2363ServicesPackage::class)->findAll());
        $this->assertCount(2, $this->_em->getRepository(DDC2363Service::class)->findAll());

        // Reinitializing EntityManager to simulate, for example, another HTTP request.
        $this->_em->clear();

        // We load again the Order...
        /** @var $order DDC2363Order */
        $order = $this->_em->getRepository(DDC2363Order::class)->find(1);

        self::assertInstanceOf(DDC2363Order::class, $order);

        // To make test pass, uncomment the following line. So ServicesPackage will be hydrated.
        // $order->getServicesPackage()->getName();

        // ... and create another ServicesPackage with two Services.
        $servicesPackage = $this->createNewServicesPackage();
        $order->services_package = $servicesPackage;

        $this->_em->persist($order);
        $this->_em->flush();
        $this->_em->flush(); // <--- This is the problem!

        // There should be one ServicesPackage (and two Services) again, right?
        $this->assertCount(1, $this->_em->getRepository(DDC2363ServicesPackage::class)->findAll());
        $this->assertCount(2, $this->_em->getRepository(DDC2363Service::class)->findAll());
    }

    private function createNewServicesPackage()
    {
        $serviceA = new DDC2363Service();
        $serviceA->name = 'BASE SERVICE A';

        $serviceB = new DDC2363Service();
        $serviceB->name = 'BASE SERVICE B';

        $servicesPackage = new DDC2363ServicesPackage();
        $servicesPackage->name = 'SERVICES PACKAGE';

        $serviceA->package = $servicesPackage;
        $serviceB->package = $servicesPackage;
        $servicesPackage->services->add($serviceA);
        $servicesPackage->services->add($serviceB);

        return $servicesPackage;
    }

}

/** @Entity */
class DDC2363Order
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @Column(type="string") */
    public $name;

    /** @OneToOne(targetEntity=DDC2363ServicesPackage::class, inversedBy="order", cascade={"persist"}) */
    public $services_package;
}

/** @Entity */
class DDC2363ServicesPackage
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @Column(type="string") */
    public $name;

    /** @OneToOne(targetEntity=DDC2363Order::class, mappedBy="services_package") */
    public $order;

    /** @OneToMany(targetEntity=DDC2363Service::class, mappedBy="package", cascade={"persist", "remove"}) */
    public $services;

    public function __construct()
    {
        $this->services = new ArrayCollection();
    }
}

/** @Entity */
class DDC2363Service
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @Column(type="string") */
    public $name;

    /** @ManyToOne(targetEntity=DDC2363ServicesPackage::class, inversedBy="services") */
    public $package;
}