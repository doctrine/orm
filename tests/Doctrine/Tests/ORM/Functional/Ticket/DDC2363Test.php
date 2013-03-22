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
    protected function setup()
    {
        parent::setup();

        $this->_schemaTool->createSchema(
            array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2363Order'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2363ServicesPackage'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2363Service'),
            )
        );
    }

    public function testIssue()
    {
        // We create an Order with related ServicesPackage that is related to two Service.
        $order = new DDC2363Order();
        $order->setName('My ORDER');

        $servicesPackage = $this->createNewServicesPackage();
        $order->setServicesPackage($servicesPackage);

        $this->_em->persist($order);
        $this->_em->flush();

        // So, we assert that there is only one ServicesPackage and 2 PurchasedService.
        // On creation there is no problem, these assertions are green.
        $this->assertCount(
            1,
            $this->_em->getRepository(__NAMESPACE__ . '\\DDC2363ServicesPackage')->findAll()
        );
        $this->assertCount(
            2,
            $this->_em->getRepository(__NAMESPACE__ . '\\DDC2363Service')->findAll()
        );

        // Reinitializing EntityManager to simulate, for example, another HTTP request.
        $this->_em->close();
        $this->_em = $this->_getEntityManager();

        // Now we load previously persisted Order and associate it with a new ServicesPackage with two new Services.
        $order = $this->_em->getRepository(__NAMESPACE__ . '\\DDC2363Order')->find(1);

        $servicesPackage = $this->createNewServicesPackage($this->_em);
        $order->setServicesPackage($servicesPackage);

        $this->_em->persist($order);
        $this->_em->flush();

        // We assert again that there is only one ServicesPackage and two Services. This is because we have
        // orphanRemoval on Order::purchased_services_package and cascade="remove" on
        // PurchasedServicesPackage::services.
        $this->assertCount(
            1,
            $this->_em->getRepository(__NAMESPACE__ . '\\DDC2363ServicesPackage')->findAll()
        );
        $this->assertCount(
            2,
            $this->_em->getRepository(__NAMESPACE__ . '\\DDC2363Service')->findAll()
        );

        // Reinitializing EntityManager to simulate, for example, another HTTP request.
        $this->_em->close();
        $this->_em = $this->_getEntityManager();

        // We load again the Order...
        $order = $this->_em->getRepository(__NAMESPACE__ . '\\DDC2363Order')->find(1);

        // To make test pass, uncomment the following line. So ServicesPackage will be hydrated.
        // $order->getServicesPackage()->getName();

        // ... and create another ServicesPackage with two Services.
        $servicesPackage = $this->createNewServicesPackage($this->_em);
        $order->setServicesPackage($servicesPackage);

        $this->_em->persist($order);
        $this->_em->flush();
        $this->_em->flush(); // <--- This is the problem!

        // There should be one ServicesPackage (and two Services) again, right?
        $this->assertCount(
            1,
            $this->_em->getRepository(__NAMESPACE__ . '\\DDC2363ServicesPackage')->findAll()
        );
        $this->assertCount(
            2,
            $this->_em->getRepository(__NAMESPACE__ . '\\DDC2363Service')->findAll()
        );
    }

    private function createNewServicesPackage()
    {
        $serviceA = new DDC2363Service();
        $serviceA->setName('BASE SERVICE A');

        $serviceB = new DDC2363Service();
        $serviceB->setName('BASE SERVICE B');

        $servicesPackage = new DDC2363ServicesPackage();
        $servicesPackage->setName('SERVICES PACKAGE');
        $servicesPackage->addService($serviceA);
        $servicesPackage->addService($serviceB);

        return $servicesPackage;
    }

}

/** @Entity */
class DDC2363Order
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /** @Column(type="string") **/
    protected $name;

    /**
     * @OneToOne(
     *   targetEntity="DDC2363ServicesPackage",
     *   inversedBy="order",
     *   cascade={"persist", "remove"},
     *   orphanRemoval=true
     * )
     */
    protected $services_package;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setServicesPackage(DDC2363ServicesPackage $services_package)
    {
        $this->services_package = $services_package;
    }

    /**
     * @return DDC2363ServicesPackage
     */
    public function getServicesPackage()
    {
        return $this->services_package;
    }
}

/** @Entity */
class DDC2363ServicesPackage
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /** @Column(type="string") **/
    protected $name;

    /**
     * @OneToOne(
     *   targetEntity="DDC2363Order",
     *   mappedBy="services_package"
     * )
     */
    protected $order;

    /**
     * @OneToMany(
     *   targetEntity="DDC2363Service",
     *   mappedBy="package",
     *   cascade={"persist", "remove"}
     * )
     */
    protected $services;

    public function __construct()
    {
        $this->services = new ArrayCollection();
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOrder(DDC2363Order $order)
    {
        $this->order = $order;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param DDC2363Service $service
     */
    public function addService(DDC2363Service $service)
    {
        $service->setPackage($this);
        $this->services->add($service);
    }

    /**
     * @return ArrayCollection
     */
    public function getServices()
    {
        return $this->services;
    }
}

/** @Entity */
class DDC2363Service
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /** @Column(type="string") **/
    protected $name;

    /**
     * @ManyToOne(targetEntity="DDC2363ServicesPackage", inversedBy="services")
     */
    protected $package;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setPackage(DDC2363ServicesPackage $package)
    {
        $this->package = $package;
    }

    /**
     * @return DDC2363ServicesPackage
     */
    public function getPackage()
    {
        return $this->package;
    }
}