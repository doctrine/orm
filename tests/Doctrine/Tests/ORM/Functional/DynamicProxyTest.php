<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\DynamicProxy\Factory;
use Doctrine\ORM\DynamicProxy\Generator;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests the generation of a proxy object for lazy loading.
 */
class DynamicProxyTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $product;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
        $this->_factory = new Factory($this->_em, new Generator($this->_em));
    }

    public function testLazyLoadsFieldValuesFromDatabase()
    {
        $product = new ECommerceProduct();
        $product->setName('Doctrine Cookbook');
        $this->_em->save($product);
        $id = $product->getId();

        $this->_em->flush();
        $this->_em->clear();

        $productProxy = $this->_factory->getReferenceProxy('Doctrine\Tests\Models\ECommerce\ECommerceProduct', array('id' => $id));
        $this->assertEquals('Doctrine Cookbook', $productProxy->getName());
    }
}
