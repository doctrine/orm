<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\OrmFunctionalTestCase;

class JoinedTableWithPropertyAsDiscriminatorColumnTest extends OrmFunctionalTestCase
{
    private const MODELS = [
        Property::class,
        Apartment::class,
        House::class,
        ReferencedProperty::class,
    ];

    private function dropSchema(): void
    {
        $this->_schemaTool->dropSchema(
            array_map(
                function ($class) {
                    return $this->_em->getClassMetadata($class);
                },
                self::MODELS
            )
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->dropSchema();
        $this->_schemaTool->createSchema(
            array_map(
                function ($class) {
                    return $this->_em->getClassMetadata($class);
                },
                self::MODELS
            )
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->dropSchema();
    }

    public function testIfQueryReturnsCorrectInstance(): void
    {
        $child = new Apartment();
        $child->type = 'penthouse';

        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $q = $this->_em->createQuery('SELECT o FROM '.Property::class.' o WHERE o.id = :id');
        $q->setParameter('id', $child->id);
        $object = $q->getSingleResult();

        $this->assertInstanceOf(Apartment::class, $object);
    }

    public function testIfRepositoryReturnsCorrectInstance(): void
    {
        $child = new Apartment();
        $child->type = 'penthouse';

        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $object = $this->_em->getRepository(Property::class)->find($child->id);
        $this->assertInstanceOf(Apartment::class, $object);
    }

    public function testIfAssociationWithRepositoryReturnsCorrectInstance(): void
    {
        $child = new Apartment();
        $child->type = 'penthouse';

        $this->_em->persist($child);

        $referenced = new ReferencedProperty();
        $referenced->property = $child;
        $this->_em->persist($referenced);

        $this->_em->flush();
        $this->_em->clear();

        $object = $this->_em->getRepository(ReferencedProperty::class)->find($referenced->id);
        $this->assertInstanceOf(ReferencedProperty::class, $object);
        $this->assertInstanceOf(Apartment::class, $object->property);
    }

    public function testIfAssociationWithQueryReturnsCorrectInstance(): void
    {
        $child = new Apartment();
        $child->type = 'penthouse';

        $this->_em->persist($child);

        $referenced = new ReferencedProperty();
        $referenced->property = $child;
        $this->_em->persist($referenced);

        $this->_em->flush();
        $this->_em->clear();

        $q = $this->_em->createQuery('SELECT o FROM '.ReferencedProperty::class.' o WHERE o.id = :id');
        $q->setParameter('id', $referenced->id);
        $object = $q->getSingleResult();

        $this->assertInstanceOf(ReferencedProperty::class, $object);
        $this->assertInstanceOf(Apartment::class, $object->property);
    }

    public function testIfAssociationWithQueryJoinedReturnsCorrectInstance(): void
    {
        $apartment = new Apartment();
        $apartment->type = 'penthouse';

        $this->_em->persist($apartment);

        $referenced = new ReferencedProperty();
        $referenced->property = $apartment;
        $this->_em->persist($referenced);

        $this->_em->flush();
        $this->_em->clear();

        $q = $this->_em->createQuery(
            'SELECT o, property FROM '.ReferencedProperty::class.' o JOIN o.property property WHERE o.id = :id'
        );
        $q->setParameter('id', $referenced->id);
        $object = $q->getSingleResult();

        $this->assertInstanceOf(ReferencedProperty::class, $object);
        $this->assertInstanceOf(Apartment::class, $object->property);
    }
}


/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *     "apartment" = "Apartment",
 *     "house" = "House",
 * })
 */
abstract class Property
{
    /**
     * @var int|null
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    public $id;
}

/**
 * @Entity
 */
class Apartment extends Property
{
    /**
     * @var string|null
     * @Column(type="string", name="apartment_type")
     */
    public $type;
}


/**
 * @Entity
 */
class House extends Property
{
}

/**
 * @Entity
 */
class ReferencedProperty
{

    /**
     * @var int|null
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    public $id;

    /**
     * @var Property|null
     * @ManyToOne(targetEntity="Property")
     */
    public $property;
}

