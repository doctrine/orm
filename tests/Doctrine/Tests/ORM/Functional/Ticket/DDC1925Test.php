<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1925
 * @group DDC-1210
 */
class DDC1925Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testIssue()
    {
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1925User'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1925Product'),
        ));

        $user = new DDC1925User();
        $user->setTitle("Test User");
        $this->_em->persist($user);

        $product = new DDC1925Product();
        $product->setTitle("Test product");
        $this->_em->persist($product);
        $this->_em->flush();

        $product->addBuyer($user);

        $this->_em->getUnitOfWork()->computeChangeSets();

        $this->_em->persist($product);
        $this->_em->flush();
    }
}

/**
 * @Table
 * @Entity
 */
class DDC1925Product
{
    /**
     * @var integer $id
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $title
     *
     * @Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @ManyToMany(targetEntity="DDC1925User")
     * @JoinTable(
     *   name="user_purchases",
     *   joinColumns={@JoinColumn(name="product_id", referencedColumnName="id")},
     *   inverseJoinColumns={@JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    private $buyers;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->buyers = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $buyers
     */
    public function setBuyers($buyers)
    {
        $this->buyers = $buyers;
    }

    /**
     * @return string
     */
    public function getBuyers()
    {
        return $this->buyers;
    }

    /**
     * @param DDC1925User $buyer
     */
    public function addBuyer(DDC1925User $buyer)
    {
        $this->buyers[] = $buyer;
    }
}

/**
 * @Table
 * @Entity
 */
class DDC1925User
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}