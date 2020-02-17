<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Tests\OrmFunctionalTestCase;
use function get_class;

/**
 * @group DDC-3033
 */
class DDC3033Test extends OrmFunctionalTestCase
{
    public function testIssue() : void
    {
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC3033User::class),
                $this->em->getClassMetadata(DDC3033Product::class),
            ]
        );

        $user       = new DDC3033User();
        $user->name = 'Test User';
        $this->em->persist($user);

        $user2       = new DDC3033User();
        $user2->name = 'Test User 2';
        $this->em->persist($user2);

        $product           = new DDC3033Product();
        $product->title    = 'Test product';
        $product->buyers[] = $user;

        $this->em->persist($product);
        $this->em->flush();

        $product->title    = 'Test Change title';
        $product->buyers[] = $user2;

        $this->em->persist($product);
        $this->em->flush();

        $expect = [
            'title' => [
                0 => 'Test product',
                1 => 'Test Change title',
            ],
        ];

        self::assertEquals($expect, $product->changeSet);
    }
}

/**
 * @ORM\Table
 * @ORM\Entity @ORM\HasLifecycleCallbacks
 */
class DDC3033Product
{
    public $changeSet = [];

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int $id
     */
    public $id;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     *
     * @var string $title
     */
    public $title;

    /**
     * @ORM\ManyToMany(targetEntity=DDC3033User::class)
     * @ORM\JoinTable(
     *   name="user_purchases_3033",
     *   joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
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
     * @ORM\PreUpdate
     */
    public function preUpdate(LifecycleEventArgs $eventArgs)
    {
    }

    /**
     * @ORM\PostUpdate
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
 * @ORM\Table
 * @ORM\Entity @ORM\HasLifecycleCallbacks
 */
class DDC3033User
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     *
     * @var string
     */
    public $name;
}
