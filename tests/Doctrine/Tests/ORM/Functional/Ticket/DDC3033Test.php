<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;

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
        $user->name = "Test User";
        $this->_em->persist($user);

        $user2 = new DDC3033User();
        $user2->name = "Test User 2";
        $this->_em->persist($user2);

        $product = new DDC3033Product();
        $product->title = "Test product";
        $product->buyers[] = $user;

        $this->_em->persist($product);
        $this->_em->flush();

        $product->title = "Test Change title";
        $product->buyers[] = $user2;

        $this->_em->persist($product);
        $this->_em->flush();

        $expect = array(
            'title' => array(
                0 => 'Test product',
                1 => 'Test Change title',
            ),
        );

        $this->assertEquals($expect, $product->changeSet);
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
    public $id;

    /**
     * @var string $title
     *
     * @Column(name="title", type="string", length=255)
     */
    public $title;

    /**
     * @ManyToMany(targetEntity="DDC3033User")
     * @JoinTable(
     *   name="user_purchases_3033",
     *   joinColumns={@JoinColumn(name="product_id", referencedColumnName="id")},
     *   inverseJoinColumns={@JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    public $buyers;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->buyers = new ArrayCollection();
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
    public $id;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=255)
     */
    public $name;
}
