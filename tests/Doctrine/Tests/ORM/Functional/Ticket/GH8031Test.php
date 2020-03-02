<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class GH8031Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH8031Invoice::class,
        ]);
    }

    public function testEntityIsFetched()
    {
        $entity = new GH8031Invoice(new GH8031InvoiceCode(1, 2020, new GH8031Nested(10)));
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        /** @var GH8031Invoice $fetched */
        $fetched = $this->_em->find(GH8031Invoice::class, $entity->getId());
        $this->assertInstanceOf(GH8031Invoice::class, $fetched);
        $this->assertSame(1, $fetched->getCode()->getNumber());
        $this->assertSame(2020, $fetched->getCode()->getYear());

        $this->_em->clear();
        $this->assertCount(
            1,
            $this->_em->getRepository(GH8031Invoice::class)->findBy([], ['code.number' => 'ASC'])
        );
    }

    public function testEmbeddableWithAssociationNotAllowed()
    {
        $cm = $this->_em->getClassMetadata(GH8031EmbeddableWithAssociation::class);

        $this->assertArrayHasKey('invoice', $cm->associationMappings);

        $cm = $this->_em->getClassMetadata(GH8031Invoice::class);

        $this->assertCount(0, $cm->associationMappings);
    }
}

/**
 * @Embeddable
 */
class GH8031EmbeddableWithAssociation
{
    /** @ManyToOne(targetEntity=GH8031Invoice::class) */
    public $invoice;
}

/**
 * @Embeddable
 */
class GH8031Nested
{
    /**
     * @Column(type="integer", name="number", length=6)
     * @var int
     */
    protected $number;

    public function __construct(int $number)
    {
        $this->number = $number;
    }

    public function getNumber() : int
    {
        return $this->number;
    }
}

/**
 * @Embeddable
 */
class GH8031InvoiceCode extends GH8031AbstractYearSequenceValue
{
}

/**
 * @Embeddable
 */
abstract class GH8031AbstractYearSequenceValue
{
    /**
     * @Column(type="integer", name="number", length=6)
     * @var int
     */
    protected $number;

    /**
     * @Column(type="smallint", name="year", length=4)
     * @var int
     */
    protected $year;

    /** @Embedded(class=GH8031Nested::class) */
    protected $nested;

    public function __construct(int $number, int $year, GH8031Nested $nested)
    {
        $this->number = $number;
        $this->year   = $year;
        $this->nested = $nested;
    }

    public function getNumber() : int
    {
        return $this->number;
    }

    public function getYear() : int
    {
        return $this->year;
    }
}

/**
 * @Entity
 */
class GH8031Invoice
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private $id;

    /**
     * @Embedded(class=GH8031InvoiceCode::class)
     * @var GH8031InvoiceCode
     */
    private $code;

    /** @Embedded(class=GH8031EmbeddableWithAssociation::class) */
    private $embeddedAssoc;

    public function __construct(GH8031InvoiceCode $code)
    {
        $this->code = $code;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCode() : GH8031InvoiceCode
    {
        return $this->code;
    }
}
