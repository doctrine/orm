<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1163
 */
class DDC1163Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1163Product'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1163SpecialProduct'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1163ProxyHolder'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1163Tag'),
        ));
    }

    public function testIssue()
    {
        $this->createSpecialProductAndProxyHolderReferencingIt();
        $this->_em->clear();

        $this->createProxyForSpecialProduct();

        $this->setPropertyAndAssignTagToSpecialProduct();

        // fails
        $this->_em->flush();
    }

    private function createSpecialProductAndProxyHolderReferencingIt()
    {
        $specialProduct = new DDC1163SpecialProduct();
        $this->_em->persist($specialProduct);

        $proxyHolder = new DDC1163ProxyHolder();
        $this->_em->persist($proxyHolder);

        $proxyHolder->setSpecialProduct($specialProduct);

        $this->_em->flush();

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
        /* @var $proxyHolder ProxyHolder */
        $proxyHolder = $this->_em->find(__NAMESPACE__ . '\\DDC1163ProxyHolder', $this->proxyHolderId);
        
        $this->assertInstanceOf(__NAMESPACE__.'\\DDC1163SpecialProduct', $proxyHolder->getSpecialProduct());
    }

    private function setPropertyAndAssignTagToSpecialProduct()
    {
        /* @var $specialProduct SpecialProduct */
        $specialProduct = $this->_em->find(__NAMESPACE__ . '\\DDC1163SpecialProduct', $this->productId);

        $this->assertInstanceOf(__NAMESPACE__.'\\DDC1163SpecialProduct', $specialProduct);
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $specialProduct);
        
        $specialProduct->setSubclassProperty('foobar');
        
        // this screams violation of law of demeter ;)
        $this->assertEquals(
            __NAMESPACE__.'\\DDC1163SpecialProduct',
            $this->_em->getUnitOfWork()->getEntityPersister(get_class($specialProduct))->getClassMetadata()->name
        );

        $tag = new DDC1163Tag('Foo');
        $this->_em->persist($tag);
        $tag->setProduct($specialProduct);
    }
}

/**
 * @Entity
 */
class DDC1163ProxyHolder
{

    /**
     * @var int
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var SpecialProduct
     * @OneToOne(targetEntity="DDC1163SpecialProduct")
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
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"special" = "DDC1163SpecialProduct"})
 */
abstract class DDC1163Product
{

    /**
     * @var int
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function getId()
    {
        return $this->id;
    }

}

/**
 * @Entity
 */
class DDC1163SpecialProduct extends DDC1163Product
{

    /**
     * @var string
     * @Column(name="subclass_property", type="string", nullable=true)
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
 * @Entity
 */
class DDC1163Tag
{

    /**
     * @var int
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var string
     * @Column(name="name", type="string")
     */
    private $name;
    /**
     * @var Product
     * @ManyToOne(targetEntity="DDC1163Product", inversedBy="tags")
     * @JoinColumns({
     *   @JoinColumn(name="product_id", referencedColumnName="id")
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