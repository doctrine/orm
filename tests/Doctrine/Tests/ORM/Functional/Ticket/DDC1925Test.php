<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1925
 * @group DDC-1210
 */
class DDC1925Test extends OrmFunctionalTestCase
{
    public function testIssue() : void
    {
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC1925User::class),
                $this->em->getClassMetadata(DDC1925Product::class),
            ]
        );

        $user = new DDC1925User();
        $user->setTitle('Test User');

        $product = new DDC1925Product();
        $product->setTitle('Test product');

        $this->em->persist($user);
        $this->em->persist($product);
        $this->em->flush();

        $product->addBuyer($user);

        $this->em->getUnitOfWork()
                  ->computeChangeSets();

        $this->em->persist($product);
        $this->em->flush();
        $this->em->clear();

        /** @var DDC1925Product $persistedProduct */
        $persistedProduct = $this->em->find(DDC1925Product::class, $product->getId());

        self::assertEquals($user, $persistedProduct->getBuyers()->first());
    }
}

/**
 * @ORM\Table
 * @ORM\Entity
 */
class DDC1925Product
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int $id
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     *
     * @var string $title
     */
    private $title;

    /**
     * @ORM\ManyToMany(targetEntity=DDC1925User::class)
     * @ORM\JoinTable(
     *   name="user_purchases",
     *   joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
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
     * @return int
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
     * @return ArrayCollection
     */
    public function getBuyers()
    {
        return $this->buyers;
    }

    public function addBuyer(DDC1925User $buyer)
    {
        $this->buyers[] = $buyer;
    }
}

/**
 * @ORM\Table
 * @ORM\Entity
 */
class DDC1925User
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     *
     * @var string
     */
    private $title;

    /**
     * Get id
     *
     * @return int
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
