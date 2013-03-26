<?php

namespace Doctrine\Tests\ORM\Functional;

/**
 * @group DDC-93
 */
class ValueObjectsTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC93Person'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC93Address'),
        ));
    }

    public function testMetadata()
    {
        $person = new DDC93Person();
        $person->name = "Tara";
        $person->address = new DDC93Address();
        $person->address->street = "United States of Tara Street";
        $person->address->zip = "12345";
        $person->address->city = "funkytown";

        $this->_em->persist($person);
        $this->_em->flush();

        $this->_em->clear();

        $person = $this->_em->find(DDC93Person::CLASSNAME, $person->id);

        $this->assertInstanceOf(DDC93Address::CLASSNAME, $person->address);
        $this->assertEquals('United States of Tara Street', $person->address->street);
        $this->assertEquals('12345', $person->address->zip);
        $this->assertEquals('funkytown', $person->address->city);
    }
}

/**
 * @Entity
 */
class DDC93Person
{
    const CLASSNAME = __CLASS__;

    /** @Id @GeneratedValue @Column(type="integer") */
    public $id;

    /** @Column(type="string") */
    public $name;

    /** @Embedded(class="DDC93Address") */
    public $address;
}

/**
 * @Embeddable
 */
class DDC93Address
{
    const CLASSNAME = __CLASS__;

    /**
     * @Column(type="string")
     */
    public $street;
    /**
     * @Column(type="string")
     */
    public $zip;
    /**
     * @Column(type="string")
     */
    public $city;
}

