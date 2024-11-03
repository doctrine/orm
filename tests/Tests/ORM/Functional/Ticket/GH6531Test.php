<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

final class GH6531Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
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
            ],
        );
    }

    #[Group('GH-6531')]
    public function testSimpleDerivedIdentity(): void
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

    #[Group('GH-6531')]
    public function testDynamicAttributes(): void
    {
        $article = new GH6531Article();
        $article->addAttribute('name', 'value');

        $this->_em->persist($article);
        $this->_em->flush();

        self::assertSame(
            $article->attributes['name'],
            $this->_em->find(GH6531ArticleAttribute::class, ['article' => $article, 'attribute' => 'name']),
        );
    }

    #[Group('GH-6531')]
    public function testJoinTableWithMetadata(): void
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
            $this->_em->find(GH6531OrderItem::class, ['product' => $product, 'order' => $order]),
        );
    }
}

#[Entity]
class GH6531User
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;
}

#[Entity]
class GH6531Address
{
    /** @var GH6531User */
    #[Id]
    #[OneToOne(targetEntity: GH6531User::class)]
    public $user;
}

#[Entity]
class GH6531Article
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @phpstan-var Collection<string, GH6531ArticleAttribute> */
    #[OneToMany(targetEntity: GH6531ArticleAttribute::class, mappedBy: 'article', cascade: ['ALL'], indexBy: 'attribute')]
    public $attributes;

    public function addAttribute(string $name, string $value): void
    {
        $this->attributes[$name] = new GH6531ArticleAttribute($name, $value, $this);
    }
}

#[Entity]
class GH6531ArticleAttribute
{
    public function __construct(
        #[Id]
        #[Column(type: 'string', length: 255)]
        public string $attribute,
        #[Column(type: 'string', length: 255)]
        public string $value,
        #[Id]
        #[ManyToOne(targetEntity: GH6531Article::class, inversedBy: 'attributes')]
        public GH6531Article $article,
    ) {
    }
}

#[Entity]
class GH6531Order
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @phpstan-var Collection<int, GH6531OrderItem> */
    #[OneToMany(targetEntity: GH6531OrderItem::class, mappedBy: 'order', cascade: ['ALL'])]
    public $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function addItem(GH6531Product $product, int $amount): void
    {
        $this->items->add(new GH6531OrderItem($this, $product, $amount));
    }
}

#[Entity]
class GH6531Product
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;
}

#[Entity]
class GH6531OrderItem
{
    public function __construct(
        #[Id]
        #[ManyToOne(targetEntity: GH6531Order::class)]
        public GH6531Order $order,
        #[Id]
        #[ManyToOne(targetEntity: GH6531Product::class)]
        public GH6531Product $product,
        #[Column(type: 'integer')]
        public int $amount = 1,
    ) {
    }
}
