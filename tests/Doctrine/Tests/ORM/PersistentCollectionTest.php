<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\OrmTestCase;

/**
 * Tests the lazy-loading capabilities of the PersistentCollection and the initialization of collections.
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @author Austin Morris <austin.morris@gmail.com>
 */
class PersistentCollectionTest extends OrmTestCase
{
    /**
     * @var PersistentCollection
     */
    protected $collection;

    private $_connectionMock;
    private $_emMock;

    protected function setUp()
    {
        parent::setUp();
        // SUT
        $this->_connectionMock = new ConnectionMock(array(), new \Doctrine\Tests\Mocks\DriverMock());
        $this->_emMock = EntityManagerMock::create($this->_connectionMock);
    }

    /**
     * Set up the PersistentCollection used for collection initialization tests.
     */
    public function setUpPersistentCollection()
    {
        $classMetaData = $this->_emMock->getClassMetadata('Doctrine\Tests\Models\ECommerce\ECommerceCart');
        $this->collection = new PersistentCollection($this->_emMock, $classMetaData, new ArrayCollection);
        $this->collection->setInitialized(false);
        $this->collection->setOwner(new ECommerceCart(), $classMetaData->getAssociationMapping('products'));
    }

    public function testCanBePutInLazyLoadingMode()
    {
        $class = $this->_emMock->getClassMetadata('Doctrine\Tests\Models\ECommerce\ECommerceProduct');
        $collection = new PersistentCollection($this->_emMock, $class, new ArrayCollection);
        $collection->setInitialized(false);
        $this->assertFalse($collection->isInitialized());
    }

    /**
     * Test that PersistentCollection::current() initializes the collection.
     */
    public function testCurrentInitializesCollection()
    {
        $this->setUpPersistentCollection();
        $this->collection->current();
        $this->assertTrue($this->collection->isInitialized());
    }

    /**
     * Test that PersistentCollection::key() initializes the collection.
     */
    public function testKeyInitializesCollection()
    {
        $this->setUpPersistentCollection();
        $this->collection->key();
        $this->assertTrue($this->collection->isInitialized());
    }

    /**
     * Test that PersistentCollection::next() initializes the collection.
     */
    public function testNextInitializesCollection()
    {
        $this->setUpPersistentCollection();
        $this->collection->next();
        $this->assertTrue($this->collection->isInitialized());
    }
}
