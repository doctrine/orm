<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Reflection\RuntimePublicReflectionProperty;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\ReflectionEmbeddedProperty;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group embedded
 * @group DDC-93
 */
class ValueObjectsTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC93Person::class),
                $this->em->getClassMetadata(DDC93Address::class),
                $this->em->getClassMetadata(DDC93Vehicle::class),
                $this->em->getClassMetadata(DDC93Car::class),
                $this->em->getClassMetadata(DDC3027Animal::class),
                $this->em->getClassMetadata(DDC3027Dog::class),
                ]
            );
        } catch(\Exception $e) {
        }
    }

    public function testMetadataHasReflectionEmbeddablesAccessible()
    {
        $classMetadata = $this->em->getClassMetadata(DDC93Person::class);

        self::assertInstanceOf(RuntimePublicReflectionProperty::class, $classMetadata->getReflectionProperty('address'));
        self::assertInstanceOf(ReflectionEmbeddedProperty::class, $classMetadata->getReflectionProperty('address.street'));
    }

    public function testCRUD()
    {
        $person = new DDC93Person();
        $person->name = "Tara";
        $person->address = new DDC93Address();
        $person->address->street = "United States of Tara Street";
        $person->address->zip = "12345";
        $person->address->city = "funkytown";
        $person->address->country = new DDC93Country('Germany');

        // 1. check saving value objects works
        $this->em->persist($person);
        $this->em->flush();

        $this->em->clear();

        // 2. check loading value objects works
        $person = $this->em->find(DDC93Person::class, $person->id);

        self::assertInstanceOf(DDC93Address::class, $person->address);
        self::assertEquals('United States of Tara Street', $person->address->street);
        self::assertEquals('12345', $person->address->zip);
        self::assertEquals('funkytown', $person->address->city);
        self::assertInstanceOf(DDC93Country::class, $person->address->country);
        self::assertEquals('Germany', $person->address->country->name);

        // 3. check changing value objects works
        $person->address->street = "Street";
        $person->address->zip = "54321";
        $person->address->city = "another town";
        $person->address->country->name = "United States of America";
        $this->em->flush();

        $this->em->clear();

        $person = $this->em->find(DDC93Person::class, $person->id);

        self::assertEquals('Street', $person->address->street);
        self::assertEquals('54321', $person->address->zip);
        self::assertEquals('another town', $person->address->city);
        self::assertEquals('United States of America', $person->address->country->name);

        // 4. check deleting works
        $personId = $person->id;;
        $this->em->remove($person);
        $this->em->flush();

        self::assertNull($this->em->find(DDC93Person::class, $personId));
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
            $person->address->country = new DDC93Country('United States of America');

            $this->em->persist($person);
        }

        $this->em->flush();
        $this->em->clear();

        $dql = "SELECT p FROM " . __NAMESPACE__ . "\DDC93Person p";
        $persons = $this->em->createQuery($dql)->getResult();

        self::assertCount(3, $persons);
        foreach ($persons as $person) {
            self::assertInstanceOf(DDC93Address::class, $person->address);
            self::assertEquals('Tree', $person->address->street);
            self::assertEquals('12345', $person->address->zip);
            self::assertEquals('funkytown', $person->address->city);
            self::assertInstanceOf(DDC93Country::class, $person->address->country);
            self::assertEquals('United States of America', $person->address->country->name);
        }

        $dql = "SELECT p FROM " . __NAMESPACE__ . "\DDC93Person p";
        $persons = $this->em->createQuery($dql)->getArrayResult();

        foreach ($persons as $person) {
            self::assertEquals('Tree', $person['address.street']);
            self::assertEquals('12345', $person['address.zip']);
            self::assertEquals('funkytown', $person['address.city']);
            self::assertEquals('United States of America', $person['address.country.name']);
        }
    }

    /**
     * @group dql
     */
    public function testDqlOnEmbeddedObjectsField()
    {
        if ($this->isSecondLevelCacheEnabled) {
            $this->markTestSkipped('SLC does not work with UPDATE/DELETE queries through EM.');
        }

        $person = new DDC93Person('Johannes', new DDC93Address('Moo', '12345', 'Karlsruhe', new DDC93Country('Germany')));
        $this->em->persist($person);
        $this->em->flush($person);

        // SELECT
        $selectDql = "SELECT p FROM " . __NAMESPACE__ ."\\DDC93Person p WHERE p.address.city = :city AND p.address.country.name = :country";
        $loadedPerson = $this->em->createQuery($selectDql)
            ->setParameter('city', 'Karlsruhe')
            ->setParameter('country', 'Germany')
            ->getSingleResult();
        self::assertEquals($person, $loadedPerson);

        self::assertNull(
            $this->em->createQuery($selectDql)
                ->setParameter('city', 'asdf')
                ->setParameter('country', 'Germany')
                ->getOneOrNullResult()
        );

        // UPDATE
        $updateDql = "UPDATE " . __NAMESPACE__ . "\\DDC93Person p SET p.address.street = :street, p.address.country.name = :country WHERE p.address.city = :city";
        $this->em->createQuery($updateDql)
            ->setParameter('street', 'Boo')
            ->setParameter('country', 'DE')
            ->setParameter('city', 'Karlsruhe')
            ->execute();

        $this->em->refresh($person);
        self::assertEquals('Boo', $person->address->street);
        self::assertEquals('DE', $person->address->country->name);

        // DELETE
        $this->em->createQuery("DELETE " . __NAMESPACE__ . "\\DDC93Person p WHERE p.address.city = :city AND p.address.country.name = :country")
            ->setParameter('city', 'Karlsruhe')
            ->setParameter('country', 'DE')
            ->execute();

        $this->em->clear();
        self::assertNull($this->em->find(DDC93Person::class, $person->id));
    }

    public function testPartialDqlOnEmbeddedObjectsField()
    {
        $person = new DDC93Person('Karl', new DDC93Address('Foo', '12345', 'Gosport', new DDC93Country('England')));
        $this->em->persist($person);
        $this->em->flush($person);
        $this->em->clear();

        // Prove that the entity was persisted correctly.
        $dql = "SELECT p FROM " . __NAMESPACE__ ."\\DDC93Person p WHERE p.name = :name";

        $person = $this->em->createQuery($dql)
            ->setParameter('name', 'Karl')
            ->getSingleResult();

        self::assertEquals('Gosport', $person->address->city);
        self::assertEquals('Foo', $person->address->street);
        self::assertEquals('12345', $person->address->zip);
        self::assertEquals('England', $person->address->country->name);

        // Clear the EM and prove that the embeddable can be the subject of a partial query.
        $this->em->clear();

        $dql = "SELECT PARTIAL p.{id,address.city} FROM " . __NAMESPACE__ ."\\DDC93Person p WHERE p.name = :name";

        $person = $this->em->createQuery($dql)
            ->setParameter('name', 'Karl')
            ->getSingleResult();

        // Selected field must be equal, all other fields must be null.
        self::assertEquals('Gosport', $person->address->city);
        self::assertNull($person->address->street);
        self::assertNull($person->address->zip);
        self::assertNull($person->address->country);
        self::assertNull($person->name);

        // Clear the EM and prove that the embeddable can be the subject of a partial query regardless of attributes positions.
        $this->em->clear();

        $dql = "SELECT PARTIAL p.{address.city, id} FROM " . __NAMESPACE__ ."\\DDC93Person p WHERE p.name = :name";

        $person = $this->em->createQuery($dql)
            ->setParameter('name', 'Karl')
            ->getSingleResult();

        // Selected field must be equal, all other fields must be null.
        self::assertEquals('Gosport', $person->address->city);
        self::assertNull($person->address->street);
        self::assertNull($person->address->zip);
        self::assertNull($person->address->country);
        self::assertNull($person->name);
    }

    public function testDqlWithNonExistentEmbeddableField()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('no field or association named address.asdfasdf');

        $this->em->createQuery("SELECT p FROM " . __NAMESPACE__ . "\\DDC93Person p WHERE p.address.asdfasdf IS NULL")
            ->execute();
    }

    public function testPartialDqlWithNonExistentEmbeddableField()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage("no mapped field named 'address.asdfasdf'");

        $this->em->createQuery("SELECT PARTIAL p.{id,address.asdfasdf} FROM " . __NAMESPACE__ . "\\DDC93Person p")
            ->execute();
    }

    public function testEmbeddableWithInheritance()
    {
        $car = new DDC93Car(new DDC93Address('Foo', '12345', 'Asdf'));
        $this->em->persist($car);
        $this->em->flush($car);

        $reloadedCar = $this->em->find(DDC93Car::class, $car->id);
        self::assertEquals($car, $reloadedCar);
    }

    public function testInlineEmbeddableWithPrefix()
    {
        $metadata = $this->em->getClassMetadata(DDC3028PersonWithPrefix::class);

        self::assertEquals('foobar_id', $metadata->getColumnName('id.id'));
        self::assertEquals('bloo_foo_id', $metadata->getColumnName('nested.nestedWithPrefix.id'));
        self::assertEquals('bloo_nestedWithEmptyPrefix_id', $metadata->getColumnName('nested.nestedWithEmptyPrefix.id'));
        self::assertEquals('bloo_id', $metadata->getColumnName('nested.nestedWithPrefixFalse.id'));
    }

    public function testInlineEmbeddableEmptyPrefix()
    {
        $metadata = $this->em->getClassMetadata(DDC3028PersonEmptyPrefix::class);

        self::assertEquals('id_id', $metadata->getColumnName('id.id'));
        self::assertEquals('nested_foo_id', $metadata->getColumnName('nested.nestedWithPrefix.id'));
        self::assertEquals('nested_nestedWithEmptyPrefix_id', $metadata->getColumnName('nested.nestedWithEmptyPrefix.id'));
        self::assertEquals('nested_id', $metadata->getColumnName('nested.nestedWithPrefixFalse.id'));
    }

    public function testInlineEmbeddablePrefixFalse()
    {
        $expectedColumnName = 'id';

        $actualColumnName = $this->em
            ->getClassMetadata(DDC3028PersonPrefixFalse::class)
            ->getColumnName('id.id');

        self::assertEquals($expectedColumnName, $actualColumnName);
    }

    public function testInlineEmbeddableInMappedSuperClass()
    {
        $isFieldMapped = $this->em
            ->getClassMetadata(DDC3027Dog::class)
            ->hasField('address.street');

        self::assertTrue($isFieldMapped);
    }

    /**
     * @dataProvider getInfiniteEmbeddableNestingData
     */
    public function testThrowsExceptionOnInfiniteEmbeddableNesting($embeddableClassName, $declaredEmbeddableClassName)
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Infinite nesting detected for embedded property %s::nested. ' .
                'You cannot embed an embeddable from the same type inside an embeddable.',
                __NAMESPACE__ . '\\' . $declaredEmbeddableClassName
            )
        );

        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(__NAMESPACE__ . '\\' . $embeddableClassName),
            ]
        );
    }

    public function getInfiniteEmbeddableNestingData()
    {
        return [
            ['DDCInfiniteNestingEmbeddable', 'DDCInfiniteNestingEmbeddable'],
            ['DDCNestingEmbeddable1', 'DDCNestingEmbeddable4'],
        ];
    }
}


/**
 * @ORM\Entity
 */
class DDC93Person
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    public $id;

    /** @ORM\Column(type="string") */
    public $name;

    /** @ORM\Embedded(class="DDC93Address") */
    public $address;

    /** @ORM\Embedded(class = "DDC93Timestamps") */
    public $timestamps;

    public function __construct($name = null, DDC93Address $address = null)
    {
        $this->name = $name;
        $this->address = $address;
        $this->timestamps = new DDC93Timestamps(new \DateTime);
    }
}

/**
 * @ORM\Embeddable
 */
class DDC93Timestamps
{
    /** @ORM\Column(type = "datetime") */
    public $createdAt;

    public function __construct(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }
}

/**
 * @ORM\Entity
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name = "t", type = "string", length = 10)
 * @ORM\DiscriminatorMap({
 *     "v" = "Doctrine\Tests\ORM\Functional\DDC93Car",
 * })
 */
abstract class DDC93Vehicle
{
    /** @ORM\Id @ORM\GeneratedValue(strategy = "AUTO") @ORM\Column(type = "integer") */
    public $id;

    /** @ORM\Embedded(class = "DDC93Address") */
    public $address;

    public function __construct(DDC93Address $address)
    {
        $this->address = $address;
    }
}

/**
 * @ORM\Entity
 */
class DDC93Car extends DDC93Vehicle
{
}

/**
 * @ORM\Embeddable
 */
class DDC93Country
{
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    public $name;

    public function __construct($name = null)
    {
        $this->name = $name;
    }
}

/**
 * @ORM\Embeddable
 */
class DDC93Address
{
    /**
     * @ORM\Column(type="string")
     */
    public $street;
    /**
     * @ORM\Column(type="string")
     */
    public $zip;
    /**
     * @ORM\Column(type="string")
     */
    public $city;
    /** @ORM\Embedded(class = "DDC93Country") */
    public $country;

    public function __construct($street = null, $zip = null, $city = null, DDC93Country $country = null)
    {
        $this->street = $street;
        $this->zip = $zip;
        $this->city = $city;
        $this->country = $country;
    }
}

/** @ORM\Entity */
class DDC93Customer
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /** @ORM\Embedded(class = "DDC93ContactInfo", columnPrefix = "contact_info_") */
    private $contactInfo;
}

/** @ORM\Embeddable */
class DDC93ContactInfo
{
    /**
     * @ORM\Column(type="string")
     */
    public $email;
    /** @ORM\Embedded(class = "DDC93Address") */
    public $address;
}

/**
 * @ORM\Entity
 */
class DDC3028PersonWithPrefix
{
    /** @ORM\Embedded(class="DDC3028Id", columnPrefix = "foobar_") */
    public $id;

    /** @ORM\Embedded(class="DDC3028NestedEmbeddable", columnPrefix = "bloo_") */
    public $nested;

    public function __construct(DDC3028Id $id = null, DDC3028NestedEmbeddable $nested = null)
    {
        $this->id = $id;
        $this->nested = $nested;
    }
}

/**
 * @ORM\Entity
 */
class DDC3028PersonEmptyPrefix
{
    /** @ORM\Embedded(class="DDC3028Id", columnPrefix = "") */
    public $id;

    /** @ORM\Embedded(class="DDC3028NestedEmbeddable", columnPrefix = "") */
    public $nested;

    public function __construct(DDC3028Id $id = null, DDC3028NestedEmbeddable $nested = null)
    {
        $this->id = $id;
        $this->nested = $nested;
    }
}

/**
 * @ORM\Entity
 */
class DDC3028PersonPrefixFalse
{
    /** @ORM\Embedded(class="DDC3028Id", columnPrefix = false) */
    public $id;

    public function __construct(DDC3028Id $id = null)
    {
        $this->id = $id;
    }
}

/**
 * @ORM\Embeddable
 */
class DDC3028Id
{
    /**
     * @ORM\Id @ORM\Column(type="string")
     */
    public $id;

    public function __construct($id = null)
    {
        $this->id = $id;
    }
}

/**
 * @ORM\Embeddable
 */
class DDC3028NestedEmbeddable
{
    /** @ORM\Embedded(class="DDC3028Id", columnPrefix = "foo_") */
    public $nestedWithPrefix;

    /** @ORM\Embedded(class="DDC3028Id", columnPrefix = "") */
    public $nestedWithEmptyPrefix;

    /** @ORM\Embedded(class="DDC3028Id", columnPrefix = false) */
    public $nestedWithPrefixFalse;

    public function __construct(
        DDC3028Id $nestedWithPrefix = null,
        DDC3028Id $nestedWithEmptyPrefix = null,
        DDC3028Id $nestedWithPrefixFalse = null
    ) {
        $this->nestedWithPrefix = $nestedWithPrefix;
        $this->nestedWithEmptyPrefix = $nestedWithEmptyPrefix;
        $this->nestedWithPrefixFalse = $nestedWithPrefixFalse;
    }
}

/**
 * @ORM\MappedSuperclass
 */
abstract class DDC3027Animal
{
    /** @ORM\Id @ORM\GeneratedValue(strategy = "AUTO") @ORM\Column(type = "integer") */
    public $id;

    /** @ORM\Embedded(class = "DDC93Address") */
    public $address;
}

/**
 * @ORM\Entity
 */
class DDC3027Dog extends DDC3027Animal
{
}

/**
 * @ORM\Embeddable
 */
class DDCInfiniteNestingEmbeddable
{
    /** @ORM\Embedded(class="DDCInfiniteNestingEmbeddable") */
    public $nested;
}

/**
 * @ORM\Embeddable
 */
class DDCNestingEmbeddable1
{
    /** @ORM\Embedded(class="DDC3028Id") */
    public $id1;

    /** @ORM\Embedded(class="DDC3028Id") */
    public $id2;

    /** @ORM\Embedded(class="DDCNestingEmbeddable2") */
    public $nested;
}

/**
 * @ORM\Embeddable
 */
class DDCNestingEmbeddable2
{
    /** @ORM\Embedded(class="DDC3028Id") */
    public $id1;

    /** @ORM\Embedded(class="DDC3028Id") */
    public $id2;

    /** @ORM\Embedded(class="DDCNestingEmbeddable3") */
    public $nested;
}

/**
 * @ORM\Embeddable
 */
class DDCNestingEmbeddable3
{
    /** @ORM\Embedded(class="DDC3028Id") */
    public $id1;

    /** @ORM\Embedded(class="DDC3028Id") */
    public $id2;

    /** @ORM\Embedded(class="DDCNestingEmbeddable4") */
    public $nested;
}

/**
 * @ORM\Embeddable
 */
class DDCNestingEmbeddable4
{
    /** @ORM\Embedded(class="DDC3028Id") */
    public $id1;

    /** @ORM\Embedded(class="DDC3028Id") */
    public $id2;

    /** @ORM\Embedded(class="DDCNestingEmbeddable1") */
    public $nested;
}
