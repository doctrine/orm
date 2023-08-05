<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10868Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH10868Shop::class,
            GH10868Offer::class,
            GH10868Order::class,
            GH10868OrderProduct::class,
        ]);
    }

    public function testReferenceAndLazyLoadProxyAreTheSame(): void
    {
        $shop         = new GH10868Shop();
        $offer        = new GH10868Offer($shop, 1);
        $order        = new GH10868Order();
        $orderProduct = new GH10868OrderProduct();

        $orderProduct->order        = $order;
        $orderProduct->productOffer = $offer;
        $order->shop                = $shop;
        $order->orderProducts->add($orderProduct);

        $this->_em->persist($shop);
        $this->_em->persist($offer);
        $this->_em->persist($order);
        $this->_em->persist($orderProduct);
        $this->_em->flush();
        $this->_em->clear();

        $order->orderProducts->count();

        $reference = $this->_em->getReference(GH10868Offer::class, [
            'shop' => $shop->id,
            'id' => $offer->id,
        ]);

        $orderProductFromOrder = $order->orderProducts->first();

        self::assertSame($reference, $orderProductFromOrder->productOffer);
    }
}

/**
 * @ORM\Entity
 */
class GH10868Order
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var ?int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="GH10868Shop")
     * @ORM\JoinColumn(name="shop_id", referencedColumnName="id", nullable=false)
     *
     * @var GH10868Shop
     */
    public $shop;

    /**
     * @ORM\OneToMany(targetEntity="GH10868OrderProduct", mappedBy="order")
     *
     * @var GH10868OrderProduct
     */
    public $orderProducts;

    public function __construct()
    {
        $this->orderProducts = new ArrayCollection();
    }
}

/**
 * @ORM\Entity
 */
class GH10868OrderProduct
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="GH10868Order", inversedBy="orderProducts", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     *
     * @var GH10868Order
     */
    public $order;

    /**
     * @ORM\ManyToOne(targetEntity="GH10868Shop")
     * @ORM\JoinColumn(name="shop_id", referencedColumnName="id")
     *
     * @var GH10868Shop
     */
    public $shop;

    /**
     * @ORM\ManyToOne(targetEntity="GH10868Offer", fetch="EXTRA_LAZY")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="productoffer_id", referencedColumnName="id"),
     *     @ORM\JoinColumn(name="shop_id", referencedColumnName="shop_id")
     * })
     *
     * @var GH10868Offer
     */
    public $productOffer;
}

/**
 * @ORM\Entity
 */
class GH10868Offer
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="bigint")
     *
     * @var ?int
     */
    public $id;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\ManyToOne(targetEntity="GH10868Shop")
     * @ORM\JoinColumn(name="shop_id", referencedColumnName="id")
     *
     * @var GH10868Shop
     */
    protected $shop;

    protected $name = 'Test';

    public function __construct(?GH10868Shop $shop = null, ?int $id = null)
    {
        $this->shop = $shop;
        $this->id   = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): GH10868Offer
    {
        $this->name = $name;

        return $this;
    }
}

/**
 * @ORM\Entity
 */
class GH10868Shop
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var ?int
     */
    public $id;
}
