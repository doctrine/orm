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
        } catch (Exception $exc) {
            
        }
    }

    public function testTicket()
    {
        $this->markTestIncomplete();
        
        $repository = $this->_em->getRepository(__NAMESPACE__ . '\DDC1430Order');
        $builder    = $repository->createQueryBuilder('o');
        $query      = $builder->select('o, COUNT(p.id) AS p_count')
                        ->leftJoin('o.products', 'p')
                        ->groupBy('o')
                        ->getQuery();
        
        
        $this->assertEquals('SELECT o, COUNT(p.id) AS p_count FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1430Order o LEFT JOIN o.products p GROUP BY o', $query->getDQL());
        $this->assertEquals('SELECT d0_.id AS id0, d0_.date AS date1, COUNT(d1_.id) AS sclr2 FROM DDC1430Order d0_ LEFT JOIN DDC1430OrderProduct d1_ ON d0_.id = d1_.order_id GROUP BY d0_.id, d0_.date', $query->getSQL());
        //echo $query->getSQL();
    }

    public function loadFixtures()
    {
        $o1 = new DDC1430Order();
        $o2 = new DDC1430Order();
        
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
     * @Column(type="integer")
     * @GeneratedValue()
     */
    protected $id;

    /**
     * @Column(type="datetime") 
     */
    private $date;

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

    public function __construct()
    {
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
     * @JoinColumn(name="order_id", referencedColumnName="id", nullable = false)
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
