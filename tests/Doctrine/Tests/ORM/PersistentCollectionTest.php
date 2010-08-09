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
        $this->assertFalse($collection->isInitialized());
    }
}
