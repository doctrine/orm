<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH6531Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                GH6531User::class,
                GH6531Address::class,
                GH6531Article::class,
                GH6531ArticleAttribute::class,
                GH6531Order::class,
                GH6531OrderItem::class,
                GH6531Product::class,
            ]
        );
    }

    /**
     * @group 6531
     */
    public function testSimpleDerivedIdentity() : void
    {
        $user          = new GH6531User();
        $address       = new GH6531Address();
        $address->user = $user;

        $this->em->persist($user);
        $this->em->persist($address);
        $this->em->flush();

        self::assertSame($user, $this->em->find(GH6531User::class, $user->id));
        self::assertSame($address, $this->em->find(GH6531Address::class, $user));
    }

    /**
     * @group 6531
     */
    public function testDynamicAttributes() : void
    {
        $article = new GH6531Article();
        $article->addAttribute('name', 'value');

        $this->em->persist($article);
        $this->em->flush();

        self::assertSame(
            $article->attributes['name'],
            $this->em->find(GH6531ArticleAttribute::class, ['article' => $article, 'attribute' => 'name'])
        );
    }

    /**
     * @group 6531
     */
    public function testJoinTableWithMetadata() : void
    {
        $product = new GH6531Product();
        $this->em->persist($product);
        $this->em->flush();

        $order = new GH6531Order();
        $order->addItem($product, 2);

        $this->em->persist($order);
        $this->em->flush();

        self::assertSame(
            $order->items->first(),
            $this->em->find(GH6531OrderItem::class, ['product' => $product, 'order' => $order])
        );
    }
}

/**
 * @ORM\Entity
 */
class GH6531User
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}

/**
 * @ORM\Entity
 */
class GH6531Address
{
    /** @ORM\Id @ORM\OneToOne(targetEntity=GH6531User::class) */
    public $user;
}

/**
 * @ORM\Entity
 */
class GH6531Article
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /** @ORM\OneToMany(targetEntity=GH6531ArticleAttribute::class, mappedBy="article", cascade={"ALL"}, indexBy="attribute") */
    public $attributes;

    public function addAttribute(string $name, string $value)
    {
        $this->attributes[$name] = new GH6531ArticleAttribute($name, $value, $this);
    }
}

/**
 * @ORM\Entity
 */
class GH6531ArticleAttribute
{
    /** @ORM\Id @ORM\ManyToOne(targetEntity=GH6531Article::class, inversedBy="attributes") */
    public $article;

    /** @ORM\Id @ORM\Column(type="string") */
    public $attribute;

    /** @ORM\Column(type="string") */
    public $value;

    public function __construct(string $name, string $value, GH6531Article $article)
    {
        $this->attribute = $name;
        $this->value     = $value;
        $this->article   = $article;
    }
}

/**
 * @ORM\Entity
 */
class GH6531Order
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /** @ORM\OneToMany(targetEntity=GH6531OrderItem::class, mappedBy="order", cascade={"ALL"}) */
    public $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function addItem(GH6531Product $product, int $amount) : void
    {
        $this->items->add(new GH6531OrderItem($this, $product, $amount));
    }
}

/**
 * @ORM\Entity
 */
class GH6531Product
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}

/**
 * @ORM\Entity
 */
class GH6531OrderItem
{
    /** @ORM\Id @ORM\ManyToOne(targetEntity=GH6531Order::class) */
    public $order;

    /** @ORM\Id @ORM\ManyToOne(targetEntity=GH6531Product::class) */
    public $product;

    /** @ORM\Column(type="integer") */
    public $amount = 1;

    public function __construct(GH6531Order $order, GH6531Product $product, int $amount = 1)
    {
        $this->order   = $order;
        $this->product = $product;
        $this->amount  = $amount;
    }
}
