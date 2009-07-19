<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Proxy\ProxyClassGenerator;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests the generation of a proxy object for lazy loading.
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
class ReferenceProxyTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $product;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
        $this->_factory = new ProxyFactory($this->_em, new ProxyClassGenerator($this->_em));
    }

    public function testLazyLoadsFieldValuesFromDatabase()
    {
        $product = new ECommerceProduct();
        $product->setName('Doctrine Cookbook');
        $this->_em->persist($product);

        $this->_em->flush();
        $this->_em->clear();
        
        $id = $product->getId();

        $productProxy = $this->_factory->getReferenceProxy('Doctrine\Tests\Models\ECommerce\ECommerceProduct', array('id' => $id));
        $this->assertEquals('Doctrine Cookbook', $productProxy->getName());
    }
}
