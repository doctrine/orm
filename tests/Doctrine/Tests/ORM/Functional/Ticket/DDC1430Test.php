<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-1430
 */
class DDC1430Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC1430Order::class),
                $this->em->getClassMetadata(DDC1430OrderProduct::class),
                ]
            );
            $this->loadFixtures();
        } catch (\Exception $exc) {

        }
    }

    public function testOrderByFields()
    {
        $repository = $this->em->getRepository(DDC1430Order::class);
        $builder    = $repository->createQueryBuilder('o');
        $query      = $builder->select('o.id, o.date, COUNT(p.id) AS p_count')
                        ->leftJoin('o.products', 'p')
                        ->groupBy('o.id, o.date')
                        ->orderBy('o.id')
                        ->getQuery();

        self::assertSQLEquals(
            'SELECT o.id, o.date, COUNT(p.id) AS p_count FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1430Order o LEFT JOIN o.products p GROUP BY o.id, o.date ORDER BY o.id ASC',
            $query->getDQL()
        );

        self::assertSQLEquals(
            'SELECT t0."order_id" AS c0, t0."created_at" AS c1, COUNT(t1."id") AS c2 FROM "DDC1430Order" t0 LEFT JOIN "DDC1430OrderProduct" t1 ON t0."order_id" = t1."order_id" GROUP BY t0."order_id", t0."created_at" ORDER BY t0."order_id" ASC',
            $query->getSQL()
        );

        $result = $query->getResult();

        self::assertEquals(2, sizeof($result));

        self::assertArrayHasKey('id', $result[0]);
        self::assertArrayHasKey('id', $result[1]);

        self::assertArrayHasKey('p_count', $result[0]);
        self::assertArrayHasKey('p_count', $result[1]);

        self::assertEquals(1, $result[0]['id']);
        self::assertEquals(2, $result[1]['id']);

        self::assertEquals(2, $result[0]['p_count']);
        self::assertEquals(3, $result[1]['p_count']);
    }

    public function testOrderByAllObjectFields()
    {
        $repository = $this->em->getRepository(DDC1430Order::class);
        $builder    = $repository->createQueryBuilder('o');
        $query      = $builder->select('o, COUNT(p.id) AS p_count')
                        ->leftJoin('o.products', 'p')
                        ->groupBy('o.id, o.date, o.status')
                        ->orderBy('o.id')
                        ->getQuery();


        self::assertSQLEquals(
            'SELECT o, COUNT(p.id) AS p_count FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1430Order o LEFT JOIN o.products p GROUP BY o.id, o.date, o.status ORDER BY o.id ASC',
            $query->getDQL()
        );

        self::assertSQLEquals(
            'SELECT t0."order_id" AS c0, t0."created_at" AS c1, t0."order_status" AS c2, COUNT(t1."id") AS c3 FROM "DDC1430Order" t0 LEFT JOIN "DDC1430OrderProduct" t1 ON t0."order_id" = t1."order_id" GROUP BY t0."order_id", t0."created_at", t0."order_status" ORDER BY t0."order_id" ASC',
            $query->getSQL()
        );

        $result = $query->getResult();

        self::assertEquals(2, sizeof($result));

        self::assertTrue($result[0][0] instanceof DDC1430Order);
        self::assertTrue($result[1][0] instanceof DDC1430Order);

        self::assertEquals($result[0][0]->getId(), 1);
        self::assertEquals($result[1][0]->getId(), 2);

        self::assertEquals($result[0]['p_count'], 2);
        self::assertEquals($result[1]['p_count'], 3);
    }

    public function testTicket()
    {
        $repository = $this->em->getRepository(DDC1430Order::class);
        $builder    = $repository->createQueryBuilder('o');
        $query      = $builder->select('o, COUNT(p.id) AS p_count')
                        ->leftJoin('o.products', 'p')
                        ->groupBy('o')
                        ->orderBy('o.id')
                        ->getQuery();


        self::assertSQLEquals(
            'SELECT o, COUNT(p.id) AS p_count FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1430Order o LEFT JOIN o.products p GROUP BY o ORDER BY o.id ASC',
            $query->getDQL()
        );

        self::assertSQLEquals(
            'SELECT t0."order_id" AS c0, t0."created_at" AS c1, t0."order_status" AS c2, COUNT(t1."id") AS c3 FROM "DDC1430Order" t0 LEFT JOIN "DDC1430OrderProduct" t1 ON t0."order_id" = t1."order_id" GROUP BY t0."order_id", t0."created_at", t0."order_status" ORDER BY t0."order_id" ASC',
            $query->getSQL()
        );

        $result = $query->getResult();

        self::assertEquals(2, sizeof($result));

        self::assertTrue($result[0][0] instanceof DDC1430Order);
        self::assertTrue($result[1][0] instanceof DDC1430Order);

        self::assertEquals($result[0][0]->getId(), 1);
        self::assertEquals($result[1][0]->getId(), 2);

        self::assertEquals($result[0]['p_count'], 2);
        self::assertEquals($result[1]['p_count'], 3);
    }

    public function loadFixtures()
    {
        $o1 = new DDC1430Order('NEW');
        $o2 = new DDC1430Order('OK');

        $o1->addProduct(new DDC1430OrderProduct(1.1));
        $o1->addProduct(new DDC1430OrderProduct(1.2));

        $o2->addProduct(new DDC1430OrderProduct(2.1));
        $o2->addProduct(new DDC1430OrderProduct(2.2));
        $o2->addProduct(new DDC1430OrderProduct(2.3));

        $this->em->persist($o1);
        $this->em->persist($o2);

        $this->em->flush();
    }
}

/**
 * @ORM\Entity
 */
class DDC1430Order
{
    /**
     * @ORM\Id
     * @ORM\Column(name="order_id", type="integer")
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(name="order_status", type="string")
     */
    private $status;

    /**
     * @ORM\OneToMany(targetEntity="DDC1430OrderProduct", mappedBy="order", cascade={"persist", "remove"})
     *
     * @var \Doctrine\Common\Collections\ArrayCollection $products
     */
    private $products;

    public function __construct($status)
    {
        $this->status   = $status;
        $this->date     = new \DateTime();
        $this->products = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param DDC1430OrderProduct $product
     */
    public function addProduct(DDC1430OrderProduct $product)
    {
        $product->setOrder($this);
        $this->products->add($product);
    }
}

/**
 * @ORM\Entity
 */
class DDC1430OrderProduct
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var DDC1430Order $order
     *
     * @ORM\ManyToOne(targetEntity="DDC1430Order", inversedBy="products")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="order_id", nullable = false)
     */
    private $order;

    /**
     * @ORM\Column(type="float")
     */
    private $value;

    /**
     * @param float $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DDC1430Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param DDC1430Order $order
     */
    public function setOrder(DDC1430Order $order)
    {
        $this->order = $order;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

}
