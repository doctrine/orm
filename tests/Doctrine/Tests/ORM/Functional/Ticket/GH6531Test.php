<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

final class GH6531Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setup();

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

        $this->_em->persist($user);
        $this->_em->persist($address);
        $this->_em->flush();

        self::assertSame($user, $this->_em->find(GH6531User::class, $user->id));
        self::assertSame($address, $this->_em->find(GH6531Address::class, $user));
    }

    /**
     * @group 6531
     */
    public function testDynamicAttributes() : void
    {
        $article = new GH6531Article();
        $article->addAttribute('name', 'value');

        $this->_em->persist($article);
        $this->_em->flush();

        self::assertSame(
            $article->attributes['name'],
            $this->_em->find(GH6531ArticleAttribute::class, ['article' => $article, 'attribute' => 'name'])
        );
    }

    /**
     * @group 6531
     */
    public function testJoinTableWithMetadata() : void
    {
        $product = new GH6531Product();
        $this->_em->persist($product);
        $this->_em->flush();

        $order = new GH6531Order();
        $order->addItem($product, 2);

        $this->_em->persist($order);
        $this->_em->flush();

        self::assertSame(
            $order->items->first(),
            $this->_em->find(GH6531OrderItem::class, ['product' => $product, 'order' => $order])
        );
    }
}

/**
 * @Entity
 */
class GH6531User
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

/**
 * @Entity
 */
class GH6531Address
{
    /** @Id @OneToOne(targetEntity=GH6531User::class) */
    public $user;
}

/**
 * @Entity
 */
class GH6531Article
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @OneToMany(targetEntity=GH6531ArticleAttribute::class, mappedBy="article", cascade={"ALL"}, indexBy="attribute") */
    public $attributes;

    public function addAttribute(string $name, string $value)
    {
        $this->attributes[$name] = new GH6531ArticleAttribute($name, $value, $this);
    }
}

/**
 * @Entity
 */
class GH6531ArticleAttribute
{
    /** @Id @ManyToOne(targetEntity=GH6531Article::class, inversedBy="attributes") */
    public $article;

    /** @Id @Column(type="string") */
    public $attribute;

    /** @Column(type="string") */
    public $value;

    public function __construct(string $name, string $value, GH6531Article $article)
    {
        $this->attribute = $name;
        $this->value     = $value;
        $this->article   = $article;
    }
}

/**
 * @Entity
 */
class GH6531Order
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @OneToMany(targetEntity=GH6531OrderItem::class, mappedBy="order", cascade={"ALL"}) */
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
 * @Entity
 */
class GH6531Product
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

/**
 * @Entity
 */
class GH6531OrderItem
{
    /** @Id @ManyToOne(targetEntity=GH6531Order::class) */
    public $order;

    /** @Id @ManyToOne(targetEntity=GH6531Product::class) */
    public $product;

    /** @Column(type="integer") */
    public $amount = 1;

    public function __construct(GH6531Order $order, GH6531Product $product, int $amount = 1)
    {
        $this->order   = $order;
        $this->product = $product;
        $this->amount  = $amount;
    }
}
