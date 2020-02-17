<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use ProxyManager\Proxy\GhostObjectInterface;
use function get_class;

/**
 * @group DDC-1163
 */
class DDC1163Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC1163Product::class),
                $this->em->getClassMetadata(DDC1163SpecialProduct::class),
                $this->em->getClassMetadata(DDC1163ProxyHolder::class),
                $this->em->getClassMetadata(DDC1163Tag::class),
            ]
        );
    }

    public function testIssue() : void
    {
        $this->createSpecialProductAndProxyHolderReferencingIt();
        $this->em->clear();

        $this->createProxyForSpecialProduct();

        $this->setPropertyAndAssignTagToSpecialProduct();

        // fails
        $this->em->flush();
    }

    private function createSpecialProductAndProxyHolderReferencingIt()
    {
        $specialProduct = new DDC1163SpecialProduct();
        $this->em->persist($specialProduct);

        $proxyHolder = new DDC1163ProxyHolder();
        $this->em->persist($proxyHolder);

        $proxyHolder->setSpecialProduct($specialProduct);

        $this->em->flush();

        $this->productId     = $specialProduct->getId();
        $this->proxyHolderId = $proxyHolder->getId();
    }

    /**
     * We want Doctrine to instantiate a lazy-load proxy for the previously created
     * 'SpecialProduct' and register it.
     *
     * When Doctrine loads the 'ProxyHolder', it will do just that because the 'ProxyHolder'
     * references the 'SpecialProduct'.
     */
    private function createProxyForSpecialProduct()
    {
        /** @var DDC1163ProxyHolder $proxyHolder */
        $proxyHolder = $this->em->find(DDC1163ProxyHolder::class, $this->proxyHolderId);

        self::assertInstanceOf(DDC1163SpecialProduct::class, $proxyHolder->getSpecialProduct());
    }

    private function setPropertyAndAssignTagToSpecialProduct()
    {
        /** @var DDC1163SpecialProduct $specialProduct */
        $specialProduct = $this->em->find(DDC1163SpecialProduct::class, $this->productId);

        self::assertInstanceOf(DDC1163SpecialProduct::class, $specialProduct);
        self::assertInstanceOf(GhostObjectInterface::class, $specialProduct);

        $specialProduct->setSubclassProperty('foobar');

        // this screams violation of law of demeter ;)
        self::assertEquals(
            DDC1163SpecialProduct::class,
            $this->em->getUnitOfWork()->getEntityPersister(get_class($specialProduct))->getClassMetadata()->getClassName()
        );

        $tag = new DDC1163Tag('Foo');
        $this->em->persist($tag);
        $tag->setProduct($specialProduct);
    }
}

/**
 * @ORM\Entity
 */
class DDC1163ProxyHolder
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
     * @ORM\OneToOne(targetEntity=DDC1163SpecialProduct::class)
     *
     * @var SpecialProduct
     */
    private $specialProduct;

    public function getId()
    {
        return $this->id;
    }

    public function setSpecialProduct(DDC1163SpecialProduct $specialProduct)
    {
        $this->specialProduct = $specialProduct;
    }

    public function getSpecialProduct()
    {
        return $this->specialProduct;
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"special" = DDC1163SpecialProduct::class})
 */
abstract class DDC1163Product
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    protected $id;

    public function getId()
    {
        return $this->id;
    }
}

/**
 * @ORM\Entity
 */
class DDC1163SpecialProduct extends DDC1163Product
{
    /**
     * @ORM\Column(name="subclass_property", type="string", nullable=true)
     *
     * @var string
     */
    private $subclassProperty;

    /**
     * @param string $value
     */
    public function setSubclassProperty($value)
    {
        $this->subclassProperty = $value;
    }
}

/**
 * @ORM\Entity
 */
class DDC1163Tag
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
     * @ORM\Column(name="name", type="string")
     *
     * @var string
     */
    private $name;
    /**
     * @ORM\ManyToOne(targetEntity=DDC1163Product::class, inversedBy="tags")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     *
     * @var Product
     */
    private $product;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param Product $product
     */
    public function setProduct(DDC1163Product $product)
    {
        $this->product = $product;
    }
}
