<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;

require_once __DIR__ . '/../TestInit.php';

/**
 * Tests the lazy-loading capabilities of the PersistentCollection.
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
class PersistentCollectionTest extends \Doctrine\Tests\OrmTestCase
{
    private $_connectionMock;
    private $_emMock;
    
    protected function setUp()
    {
        parent::setUp();
        // SUT
        $this->_connectionMock = new ConnectionMock(array(), new \Doctrine\Tests\Mocks\DriverMock());
        $this->_emMock = EntityManagerMock::create($this->_connectionMock);
    }

    public function testCanBePutInLazyLoadingMode()
    {
        $class = $this->_emMock->getClassMetadata('Doctrine\Tests\Models\ECommerce\ECommerceProduct');
        $collection = new PersistentCollection($this->_emMock, $class, new ArrayCollection);
        $collection->setInitialized(false);
    }

    public function testQueriesAssociationToLoadItself()
    {
        $class = $this->_emMock->getClassMetadata('Doctrine\Tests\Models\ECommerce\ECommerceProduct');
        $collection = new PersistentCollection($this->_emMock, $class, new ArrayCollection);
        $collection->setInitialized(false);

        $association = $this->getMock('Doctrine\ORM\Mapping\OneToManyMapping', array('load'), array(), '', false, false, false);
        $association->targetEntityName = 'Doctrine\Tests\Models\ECommerce\ECommerceFeature';
        $product = new ECommerceProduct();
        $association->expects($this->once())
                    ->method('load')
                    ->with($product, $this->isInstanceOf($collection), $this->isInstanceOf($this->_emMock));
        $collection->setOwner($product, $association);

        count($collection);
    }
}
