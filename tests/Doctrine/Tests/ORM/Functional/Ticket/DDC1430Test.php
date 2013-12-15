<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1430
 */
class DDC1430Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1430Order'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1430OrderProduct'),
            ));
            $this->loadFixtures();
        } catch (\Exception $exc) {

        }
    }

    public function testOrderByFields()
    {
        $repository = $this->_em->getRepository(__NAMESPACE__ . '\DDC1430Order');
        $builder    = $repository->createQueryBuilder('o');
        $query      = $builder->select('o.id, o.date, COUNT(p.id) AS p_count')
                        ->leftJoin('o.products', 'p')
                        ->groupBy('o.id, o.date')
                        ->orderBy('o.id')
                        ->getQuery();

        $this->assertEquals('SELECT o.id, o.date, COUNT(p.id) AS p_count FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1430Order o LEFT JOIN o.products p GROUP BY o.id, o.date ORDER BY o.id ASC', $query->getDQL());
        $this->assertEquals('SELECT d0_.order_id AS order_id0, d0_.created_at AS created_at1, COUNT(d1_.id) AS sclr2 FROM DDC1430Order d0_ LEFT JOIN DDC1430OrderProduct d1_ ON d0_.order_id = d1_.order_id GROUP BY d0_.order_id, d0_.created_at ORDER BY d0_.order_id ASC', $query->getSQL());


        $result = $query->getResult();

        $this->assertEquals(2, sizeof($result));

        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);

        $this->assertArrayHasKey('p_count', $result[0]);
        $this->assertArrayHasKey('p_count', $result[1]);

        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals(2, $result[1]['id']);

        $this->assertEquals(2, $result[0]['p_count']);
        $this->assertEquals(3, $result[1]['p_count']);
    }

    public function testOrderByAllObjectFields()
    {
        $repository = $this->_em->getRepository(__NAMESPACE__ . '\DDC1430Order');
        $builder    = $repository->createQueryBuilder('o');
        $query      = $builder->select('o, COUNT(p.id) AS p_count')
                        ->leftJoin('o.products', 'p')
                        ->groupBy('o.id, o.date, o.status')
                        ->orderBy('o.id')
                        ->getQuery();


        $this->assertEquals('SELECT o, COUNT(p.id) AS p_count FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1430Order o LEFT JOIN o.products p GROUP BY o.id, o.date, o.status ORDER BY o.id ASC', $query->getDQL());
        $this->assertEquals('SELECT d0_.order_id AS order_id0, d0_.created_at AS created_at1, d0_.order_status AS order_status2, COUNT(d1_.id) AS sclr3 FROM DDC1430Order d0_ LEFT JOIN DDC1430OrderProduct d1_ ON d0_.order_id = d1_.order_id GROUP BY d0_.order_id, d0_.created_at, d0_.order_status ORDER BY d0_.order_id ASC', $query->getSQL());

        $result = $query->getResult();


        $this->assertEquals(2, sizeof($result));

        $this->assertTrue($result[0][0] instanceof DDC1430Order);
        $this->assertTrue($result[1][0] instanceof DDC1430Order);

        $this->assertEquals($result[0][0]->getId(), 1);
        $this->assertEquals($result[1][0]->getId(), 2);

        $this->assertEquals($result[0]['p_count'], 2);
        $this->assertEquals($result[1]['p_count'], 3);
    }

    public function testTicket()
    {
        $repository = $this->_em->getRepository(__NAMESPACE__ . '\DDC1430Order');
        $builder    = $repository->createQueryBuilder('o');
        $query      = $builder->select('o, COUNT(p.id) AS p_count')
                        ->leftJoin('o.products', 'p')
                        ->groupBy('o')
                        ->orderBy('o.id')
                        ->getQuery();


        $this->assertEquals('SELECT o, COUNT(p.id) AS p_count FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1430Order o LEFT JOIN o.products p GROUP BY o ORDER BY o.id ASC', $query->getDQL());
        $this->assertEquals('SELECT d0_.order_id AS order_id0, d0_.created_at AS created_at1, d0_.order_status AS order_status2, COUNT(d1_.id) AS sclr3 FROM DDC1430Order d0_ LEFT JOIN DDC1430OrderProduct d1_ ON d0_.order_id = d1_.order_id GROUP BY d0_.order_id, d0_.created_at, d0_.order_status ORDER BY d0_.order_id ASC', $query->getSQL());


        $result = $query->getResult();

        $this->assertEquals(2, sizeof($result));

        $this->assertTrue($result[0][0] instanceof DDC1430Order);
        $this->assertTrue($result[1][0] instanceof DDC1430Order);

        $this->assertEquals($result[0][0]->getId(), 1);
        $this->assertEquals($result[1][0]->getId(), 2);

        $this->assertEquals($result[0]['p_count'], 2);
        $this->assertEquals($result[1]['p_count'], 3);
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

        $this->_em->persist($o1);
        $this->_em->persist($o2);

        $this->_em->flush();
    }

}

/**
 * @Entity
 */
class DDC1430Order
{

    /**
     * @Id
     * @Column(name="order_id", type="integer")
     * @GeneratedValue()
     */
    protected $id;

    /**
     * @Column(name="created_at", type="datetime")
     */
    private $date;

    /**
     * @Column(name="order_status", type="string")
     */
    private $status;

    /**
     * @OneToMany(targetEntity="DDC1430OrderProduct", mappedBy="order", cascade={"persist", "remove"})
     *
     * @var \Doctrine\Common\Collections\ArrayCollection $products
     */
    private $products;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function __construct($status)
    {
        $this->status   = $status;
        $this->date     = new \DateTime();
        $this->products = new \Doctrine\Common\Collections\ArrayCollection();
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
 * @Entity
 */
class DDC1430OrderProduct
{

     /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue()
     */
    protected $id;

    /**
     * @var DDC1430Order $order
     *
     * @ManyToOne(targetEntity="DDC1430Order", inversedBy="products")
     * @JoinColumn(name="order_id", referencedColumnName="order_id", nullable = false)
     */
    private $order;

    /**
     * @column(type="float")
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
     * @return integer
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