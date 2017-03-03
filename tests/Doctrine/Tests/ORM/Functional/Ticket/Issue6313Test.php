<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Common\Collections\ArrayCollection;

final class Issue6313Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(Issue6313_Unit::class),
                $this->_em->getClassMetadata(Issue6313_Port::class),
                $this->_em->getClassMetadata(Issue6313_Container::class),
                $this->_em->getClassMetadata(Issue6313_Container1::class),
                $this->_em->getClassMetadata(Issue6313_Container2::class),
                $this->_em->getClassMetadata(Issue6313_Container3::class),
                $this->_em->getClassMetadata(Issue6313_Container4::class),
            ]
        );
    }

    public function testOrphanWithCollectionShouldBeDeletedOnOneToManyRelation()
    {
        $unit1 = new Issue6313_Unit1();

        $port1 = new Issue6313_Port();
        $port1->addContainer(new Issue6313_Container1());
        $port1->addContainer(new Issue6313_Container2());

        $port2 = new Issue6313_Port();
        $port2->addContainer(new Issue6313_Container1());
        $port2->addContainer(new Issue6313_Container2());

        $unit1->addPort($port1);
        $unit1->addPort($port2);

        $this->_em->persist($unit1);
        $this->_em->flush();
        $this->_em->clear();

        $unit1 = $this->_em->find(Issue6313_Unit::class, $unit1->id);
        $port1 = $this->_em->find(Issue6313_Port::class, $port1->id);
        $unit1->removePort($port1);
        $this->_em->flush();
        $this->_em->clear();

        $unit1 = $this->_em->find(Issue6313_Unit::class, $unit1->id);

        self::assertEquals(1, $unit1->ports->count());
    }
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type_id", type="integer")
 * @DiscriminatorMap({1 = "Issue6313_Unit1", 2 = "Issue6313_Unit2"})
 */
abstract class Issue6313_Unit
{
    /**
     * @var integer
     *
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    public $id;

    /**
     * @var ArrayCollection
     *
     * @OneToMany(targetEntity="Issue6313_Port", mappedBy="unit", fetch="EXTRA_LAZY", cascade={"all"}, orphanRemoval=true)
     */
    public $ports;

    public function __construct()
    {
        $this->ports = new ArrayCollection();
    }

    public function addPort(Issue6313_Port $port)
    {
        $this->ports->add($port);
        $port->unit = $this;
    }

    public function removePort(Issue6313_Port $port)
    {
        $this->ports->removeElement($port);
        $port->unit = null;
    }
}

/**
 * @Entity
 */
class Issue6313_Unit1 extends Issue6313_Unit
{
}

/**
 * @Entity
 */
class Issue6313_Unit2 extends Issue6313_Unit
{
}

/**
 * @Entity
 */
class Issue6313_Port
{
    /**
     * @var integer
     *
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    public $id;

    /**
     * @var Issue6313_Unit
     *
     * @ManyToOne(targetEntity="Issue6313_Unit", inversedBy="ports")
     * @JoinColumn(name="unit_id", referencedColumnName="id")
     */
    public $unit;

    /**
     * @var ArrayCollection
     *
     * @OneToMany(targetEntity="Issue6313_Container", mappedBy="port", fetch="EXTRA_LAZY", cascade={"all"}, orphanRemoval=true)
     */
    public $containers;

    public function __construct()
    {
        $this->containers = new ArrayCollection();
    }

    public function addContainer(Issue6313_Container $container)
    {
        $this->containers->add($container);
        $container->port = $this;
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type_id", type="integer")
 * @DiscriminatorMap({1 = "Issue6313_Container1", 2 = "Issue6313_Container2", 3 = "Issue6313_Container3", 4 = "Issue6313_Container4"})
 */
abstract class Issue6313_Container
{
    /**
     * @var integer
     *
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    public $id;

    /**
     * @var Issue6313_Port
     *
     * @ManyToOne(targetEntity="Issue6313_Port", inversedBy="containers")
     * @JoinColumn(name="port_id", referencedColumnName="id")
     */
    public $port;

    /**
     * @var Issue6313_Container
     *
     * @ManyToOne(targetEntity="Issue6313_Container", inversedBy="containers")
     * @JoinColumn(name="upper_id", referencedColumnName="id")
     */
    public $upper;

    /**
     * @var ArrayCollection
     *
     * @OneToMany(targetEntity="Issue6313_Container", mappedBy="upper", fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    public $containers;

    public function __construct()
    {
        $this->containers = new ArrayCollection();
    }

    public function addContainer(Issue6313_Container $container)
    {
        $this->containers->add($container);
        $container->port = $this->port;
        $container->upper = $this;
    }
}

/**
 * @Entity
 */
class Issue6313_Container1 extends Issue6313_Container
{
    /** @Column(type="string", length=255) */
    public $field1 = '';
}

/**
 * @Entity
 */
class Issue6313_Container2 extends Issue6313_Container
{
    /** @Column(type="string", length=255) */
    public $field2 = '';
}

/**
 * @Entity
 */
class Issue6313_Container3 extends Issue6313_Container
{
    /** @Column(type="string", length=255) */
    public $field3 = '';
}

/**
 * @Entity
 */
class Issue6313_Container4 extends Issue6313_Container
{
    /** @Column(type="string", length=255) */
    public $field4 = '';
}
