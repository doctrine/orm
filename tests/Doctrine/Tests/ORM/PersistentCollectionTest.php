<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
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

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $emMock;

    protected function setUp()
    {
        parent::setUp();

        $this->emMock = EntityManagerMock::create(new ConnectionMock([], new DriverMock()));
    }

    /**
     * Set up the PersistentCollection used for collection initialization tests.
     */
    public function setUpPersistentCollection()
    {
        $classMetaData = $this->emMock->getClassMetadata(ECommerceCart::class);
        $this->collection = new PersistentCollection($this->emMock, $classMetaData, new ArrayCollection);
        $this->collection->setInitialized(false);
        $this->collection->setOwner(new ECommerceCart(), $classMetaData->getProperty('products'));
    }

    public function testCanBePutInLazyLoadingMode()
    {
        $class = $this->emMock->getClassMetadata(ECommerceProduct::class);
        $collection = new PersistentCollection($this->emMock, $class, new ArrayCollection);
        $collection->setInitialized(false);
        self::assertFalse($collection->isInitialized());
    }

    /**
     * Test that PersistentCollection::current() initializes the collection.
     */
    public function testCurrentInitializesCollection()
    {
        $this->setUpPersistentCollection();
        $this->collection->current();
        self::assertTrue($this->collection->isInitialized());
    }

    /**
     * Test that PersistentCollection::key() initializes the collection.
     */
    public function testKeyInitializesCollection()
    {
        $this->setUpPersistentCollection();
        $this->collection->key();
        self::assertTrue($this->collection->isInitialized());
    }

    /**
     * Test that PersistentCollection::next() initializes the collection.
     */
    public function testNextInitializesCollection()
    {
        $this->setUpPersistentCollection();
        $this->collection->next();
        self::assertTrue($this->collection->isInitialized());
    }

    /**
     * @group DDC-3382
     */
    public function testNonObjects()
    {
        $this->setUpPersistentCollection();

        self::assertEmpty($this->collection);

        $this->collection->add("dummy");

        self::assertNotEmpty($this->collection);

        $product = new ECommerceProduct();

        $this->collection->set(1, $product);
        $this->collection->set(2, "dummy");
        $this->collection->set(3, null);

        self::assertSame($product, $this->collection->get(1));
        self::assertSame("dummy", $this->collection->get(2));
        self::assertSame(null, $this->collection->get(3));
    }

    /**
     * @group 6110
     */
    public function testRemovingElementsAlsoRemovesKeys()
    {
        $this->setUpPersistentCollection();

        $this->collection->add('dummy');
        $this->assertEquals([0], array_keys($this->collection->toArray()));

        $this->collection->removeElement('dummy');
        $this->assertEquals([], array_keys($this->collection->toArray()));
    }

    /**
     * @group 6110
     */
    public function testClearWillAlsoClearKeys()
    {
        $this->setUpPersistentCollection();

        $this->collection->add('dummy');
        $this->collection->clear();
        $this->assertEquals([], array_keys($this->collection->toArray()));
    }

    /**
     * @group 6110
     */
    public function testClearWillAlsoResetKeyPositions()
    {
        $this->setUpPersistentCollection();

        $this->collection->add('dummy');
        $this->collection->removeElement('dummy');
        $this->collection->clear();
        $this->collection->add('dummy');
        $this->assertEquals([0], array_keys($this->collection->toArray()));
    }
}
