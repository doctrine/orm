<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Proxy\Proxy;

/**
 * @group DDC-1163
 */
class DDC1163Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
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

    public function testIssue()
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

        $this->productId = $specialProduct->getId();
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
        /* @var $proxyHolder DDC1163ProxyHolder */
        $proxyHolder = $this->em->find(DDC1163ProxyHolder::class, $this->proxyHolderId);

        self::assertInstanceOf(DDC1163SpecialProduct::class, $proxyHolder->getSpecialProduct());
    }

    private function setPropertyAndAssignTagToSpecialProduct()
    {
        /* @var $specialProduct DDC1163SpecialProduct */
        $specialProduct = $this->em->find(DDC1163SpecialProduct::class, $this->productId);

        self::assertInstanceOf(DDC1163SpecialProduct::class, $specialProduct);
        self::assertInstanceOf(Proxy::class, $specialProduct);

        $specialProduct->setSubclassProperty('foobar');

        // this screams violation of law of demeter ;)
        self::assertEquals(
            DDC1163SpecialProduct::class,
            $this->em->getUnitOfWork()->getEntityPersister(get_class($specialProduct))->getClassMetadata()->name
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
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var SpecialProduct
     * @ORM\OneToOne(targetEntity="DDC1163SpecialProduct")
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
 * @ORM\DiscriminatorMap({"special" = "DDC1163SpecialProduct"})
 */
abstract class DDC1163Product
{

    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
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
     * @var string
     * @ORM\Column(name="subclass_property", type="string", nullable=true)
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
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var string
     * @ORM\Column(name="name", type="string")
     */
    private $name;
    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="DDC1163Product", inversedBy="tags")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
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
