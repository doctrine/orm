<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-3033
 */
class DDC3033Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testIssue()
    {
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC3033User'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC3033Product'),
        ));

        $user = new DDC3033User();
        $user->setTitle("Test User");
        $this->_em->persist($user);

        $user2 = new DDC3033User();
        $user2->setTitle("Test User 2");
        $this->_em->persist($user2);

        $product = new DDC3033Product();
        $product->setTitle("Test product");
        $product->addBuyer($user);

	      $this->_em->persist($product);
        $this->_em->flush();

	      $product->setTitle("Test Change title");
	      $product->addBuyer($user2);

	      $this->_em->persist($product);
	      $this->_em->flush();

	      $expect = array(
            'title' => array(
		            0 => 'Test product',
		            1 => 'Test Change title',
	          ),
	      );

	      $this->assertEquals(print_r($expect, true), print_r($product->changeSet, true));
    }
}

/**
 * @Table
 * @Entity @HasLifecycleCallbacks
 */
class DDC3033Product
{
    public $changeSet = array();

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
     * @ManyToMany(targetEntity="DDC3033User")
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
     * @param DDC3033User $buyer
     */
    public function addBuyer(DDC3033User $buyer)
    {
        $this->buyers[] = $buyer;
    }

    /**
     * @PreUpdate
     */
    public function preUpdate(LifecycleEventArgs $eventArgs)
    {
    }

    /**
     * @PostUpdate
     */
    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $em            = $eventArgs->getEntityManager();
        $uow           = $em->getUnitOfWork();
        $entity        = $eventArgs->getEntity();
        $classMetadata = $em->getClassMetadata(get_class($entity));

	      $uow->computeChangeSet($classMetadata, $entity);
        $this->changeSet = $uow->getEntityChangeSet($entity);
    }
}

/**
 * @Table
 * @Entity @HasLifecycleCallbacks
 */
class DDC3033User
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
