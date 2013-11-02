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

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC93Person'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC93Address'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC93Vehicle'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC93Car'),
            ));
        } catch(\Exception $e) {
        }
    }

    public function testCRUD()
    {
        $person = new DDC93Person();
        $person->name = "Tara";
        $person->address = new DDC93Address();
        $person->address->street = "United States of Tara Street";
        $person->address->zip = "12345";
        $person->address->city = "funkytown";

        // 1. check saving value objects works
        $this->_em->persist($person);
        $this->_em->flush();

        $this->_em->clear();

        // 2. check loading value objects works
        $person = $this->_em->find(DDC93Person::CLASSNAME, $person->id);

        $this->assertInstanceOf(DDC93Address::CLASSNAME, $person->address);
        $this->assertEquals('United States of Tara Street', $person->address->street);
        $this->assertEquals('12345', $person->address->zip);
        $this->assertEquals('funkytown', $person->address->city);

        // 3. check changing value objects works
        $person->address->street = "Street";
        $person->address->zip = "54321";
        $person->address->city = "another town";
        $this->_em->flush();

        $this->_em->clear();

        $person = $this->_em->find(DDC93Person::CLASSNAME, $person->id);

        $this->assertEquals('Street', $person->address->street);
        $this->assertEquals('54321', $person->address->zip);
        $this->assertEquals('another town', $person->address->city);

        // 4. check deleting works
        $personId = $person->id;;
        $this->_em->remove($person);
        $this->_em->flush();

        $this->assertNull($this->_em->find(DDC93Person::CLASSNAME, $personId));
    }

    public function testLoadDql()
    {
        for ($i = 0; $i < 3; $i++) {
            $person = new DDC93Person();
            $person->name = "Donkey Kong$i";
            $person->address = new DDC93Address();
            $person->address->street = "Tree";
            $person->address->zip = "12345";
            $person->address->city = "funkytown";

            $this->_em->persist($person);
        }

        $this->_em->flush();
        $this->_em->clear();

        $dql = "SELECT p FROM " . __NAMESPACE__ . "\DDC93Person p";
        $persons = $this->_em->createQuery($dql)->getResult();

        $this->assertCount(3, $persons);
        foreach ($persons as $person) {
            $this->assertInstanceOf(DDC93Address::CLASSNAME, $person->address);
            $this->assertEquals('Tree', $person->address->street);
            $this->assertEquals('12345', $person->address->zip);
            $this->assertEquals('funkytown', $person->address->city);
        }

        $dql = "SELECT p FROM " . __NAMESPACE__ . "\DDC93Person p";
        $persons = $this->_em->createQuery($dql)->getArrayResult();

        foreach ($persons as $person) {
            $this->assertEquals('Tree', $person['address.street']);
            $this->assertEquals('12345', $person['address.zip']);
            $this->assertEquals('funkytown', $person['address.city']);
        }
    }

    /**
     * @group dql
     */
    public function testDqlOnEmbeddedObjectsField()
    {
        $person = new DDC93Person('Johannes', new DDC93Address('Moo', '12345', 'Karlsruhe'));
        $this->_em->persist($person);
        $this->_em->flush($person);

        // SELECT
        $selectDql = "SELECT p FROM " . __NAMESPACE__ ."\\DDC93Person p WHERE p.address.city = :city";
        $loadedPerson = $this->_em->createQuery($selectDql)
            ->setParameter('city', 'Karlsruhe')
            ->getSingleResult();
        $this->assertEquals($person, $loadedPerson);

        $this->assertNull($this->_em->createQuery($selectDql)->setParameter('city', 'asdf')->getOneOrNullResult());

        // UPDATE
        $updateDql = "UPDATE " . __NAMESPACE__ . "\\DDC93Person p SET p.address.street = :street WHERE p.address.city = :city";
        $this->_em->createQuery($updateDql)
            ->setParameter('street', 'Boo')
            ->setParameter('city', 'Karlsruhe')
            ->execute();

        $this->_em->refresh($person);
        $this->assertEquals('Boo', $person->address->street);

        // DELETE
        $this->_em->createQuery("DELETE " . __NAMESPACE__ . "\\DDC93Person p WHERE p.address.city = :city")
            ->setParameter('city', 'Karlsruhe')
            ->execute();

        $this->_em->clear();
        $this->assertNull($this->_em->find(__NAMESPACE__.'\\DDC93Person', $person->id));
    }

    public function testDqlWithNonExistentEmbeddableField()
    {
        $this->setExpectedException('Doctrine\ORM\Query\QueryException', 'no field or association named address.asdfasdf');

        $this->_em->createQuery("SELECT p FROM " . __NAMESPACE__ . "\\DDC93Person p WHERE p.address.asdfasdf IS NULL")
            ->execute();
    }

    public function testEmbeddableWithInheritance()
    {
        $car = new DDC93Car(new DDC93Address('Foo', '12345', 'Asdf'));
        $this->_em->persist($car);
        $this->_em->flush($car);

        $reloadedCar = $this->_em->find(__NAMESPACE__.'\\DDC93Car', $car->id);
        $this->assertEquals($car, $reloadedCar);
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

    /** @Embedded(class = "DDC93Timestamps") */
    public $timestamps;

    public function __construct($name = null, DDC93Address $address = null)
    {
        $this->name = $name;
        $this->address = $address;
        $this->timestamps = new DDC93Timestamps(new \DateTime);
    }
}

/**
 * @Embeddable
 */
class DDC93Timestamps
{
    /** @Column(type = "datetime") */
    public $createdAt;

    public function __construct(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }
}

/**
 * @Entity
 *
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name = "t", type = "string", length = 10)
 * @DiscriminatorMap({
 *     "v" = "Doctrine\Tests\ORM\Functional\DDC93Car",
 * })
 */
abstract class DDC93Vehicle
{
    /** @Id @GeneratedValue(strategy = "AUTO") @Column(type = "integer") */
    public $id;

    /** @Embedded(class = "DDC93Address") */
    public $address;

    public function __construct(DDC93Address $address)
    {
        $this->address = $address;
    }
}

/**
 * @Entity
 */
class DDC93Car extends DDC93Vehicle
{
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

    public function __construct($street = null, $zip = null, $city = null)
    {
        $this->street = $street;
        $this->zip = $zip;
        $this->city = $city;
    }
}

