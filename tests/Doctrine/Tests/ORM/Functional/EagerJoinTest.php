<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Tests whether a filter is activated when a collection is set to eager join.
 */
class EagerJoinTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema([
                $this->_em->getClassMetadata(Order::class),
                $this->_em->getClassMetadata(Product::class),
                $this->_em->getClassMetadata(Feature::class),
            ]);
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    public function testEagerJoin()
    {
        $product = new Product(
            'car',
            [
                new Feature('Drives', true),
                new Feature('Steering wheel', true),
                new Feature('Crash into stuff', false)
            ]
        );

        $order = new Order($product);
        $this->_em->persist($product);
        $this->_em->persist($order);
        $this->_em->flush();

        $conf = $this->_em->getConfiguration();
        $conf->addFilter('visibility', '\Doctrine\Tests\ORM\Functional\VisibilityFilter');
        $this->_em->getFilters()->enable('visibility');
        $this->_em->clear();

        $order = $this->_em->find(Order::class, $order->getId());
        self::assertContains(
            'Doctrine\Tests\ORM\Functional\Feature',
            VisibilityFilter::$classes,
            'The VisibilityFilter should have been called for Feature.'
        );
        self::assertCount(2, $order->getProduct()->getFeatures());
    }
}

/**
 * @Entity
 * @Table(name="`order`")
 */
class Order
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="Product", inversedBy="orders")
     * @JoinColumn(name="product_id")
     * @var Product
     */
    private $product;

    /**
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }
}

/**
 * @Entity
 * @Table(name="product")
 */
class Product
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @OneToMany(targetEntity="Order", mappedBy="order")
     */
    private $orders;

    /**
     * @Column(type="string")
     * @var string
     */
    private $description;

    /**
     * @OneToMany(
     *     targetEntity="Feature",
     *     mappedBy="product",
     *     cascade={"persist"},
     *     fetch="EAGER"
     * )
     * @var ArrayCollection
     */
    private $features;

    /**
     * @param string $description
     * @param array $features
     */
    public function __construct($description, array $features)
    {
        $this->description = $description;
        $this->features    = new ArrayCollection($features);

        foreach ($features as $feature) {
            $feature->setProduct($this);
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getFeatures()
    {
        return $this->features;
    }
}

/**
 * @Entity
 * @Table(name="feature")
 */
class Feature
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="string")
     * @var string
     */
    private $description;

    /**
     * @Column(type="boolean")
     * @var bool
     */
    private $visible;

    /**
     * @todo If we can't replicate it, this used to be Id
     * @ManyToOne(targetEntity="Product", inversedBy="features")
     * @JoinColumn(name="product_id")
     * @var Product
     */
    private $product;

    /**
     * @param string $description
     * @param bool $visible
     */
    public function __construct($description, $visible = true)
    {
        $this->description = $description;
        $this->visible = (bool)$visible;
    }

    /**
     * @param Product $product
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
    }
}

/**
 * Filter to ensure only visible features are retrieved.
 */
class VisibilityFilter extends SQLFilter
{
    /**
     * Contains all classes this filter was called for.
     *
     * @var array
     */
    public static $classes = [];

    /**
     * {@inheritdoc}
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        self::$classes[] = $targetEntity->name;
        if ($targetEntity->name !== Feature::class) {
            return '';
        }
        return $targetTableAlias . '.visible = 1';
    }
}

