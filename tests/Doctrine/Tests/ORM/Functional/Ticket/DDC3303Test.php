<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class DDC3303Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema([$this->_em->getClassMetadata(DDC3303Employee::class)]);
    }

    /**
     * @group 4097
     * @group 4277
     * @group 5867
     *
     * When using an embedded field in an inheritance, private properties should also be inherited.
     */
    public function testEmbeddedObjectsAreAlsoInherited()
    {
        $employee = new DDC3303Employee(
            'John Doe',
            new DDC3303Address('Somewhere', 123, 'Over the rainbow'),
            'Doctrine Inc'
        );

        $this->_em->persist($employee);
        $this->_em->flush();
        $this->_em->clear();

        self::assertEquals($employee, $this->_em->find(DDC3303Employee::class, 'John Doe'));
    }
}

/** @MappedSuperclass */
abstract class DDC3303Person
{
    /** @Id @GeneratedValue(strategy="NONE") @Column(type="string") @var string */
    private $name;

    /** @Embedded(class="DDC3303Address") @var DDC3303Address */
    private $address;

    public function __construct($name, DDC3303Address $address)
    {
        $this->name    = $name;
        $this->address = $address;
    }
}

/**
 * @Embeddable
 */
class DDC3303Address
{
    /** @Column(type="string") @var string */
    private $street;

    /** @Column(type="integer") @var int */
    private $number;

    /** @Column(type="string") @var string */
    private $city;

    public function __construct($street, $number, $city)
    {
        $this->street = $street;
        $this->number = $number;
        $this->city   = $city;
    }
}

/**
 * @Entity
 * @Table(name="ddc3303_employee")
 */
class DDC3303Employee extends DDC3303Person
{
    /** @Column(type="string") @var string */
    private $company;

    public function __construct($name, DDC3303Address $address, $company)
    {
        parent::__construct($name, $address);

        $this->company = $company;
    }
}
