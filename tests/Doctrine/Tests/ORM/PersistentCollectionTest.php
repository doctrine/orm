<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
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
     * @var EntityManagerMock
     */
    private $_emMock;

    protected function setUp()
    {
        parent::setUp();

        $this->_emMock = EntityManagerMock::create(new ConnectionMock([], new DriverMock()));

        $this->setUpPersistentCollection();
    }

    /**
     * Set up the PersistentCollection used for collection initialization tests.
     */
    public function setUpPersistentCollection()
    {
        $classMetaData = $this->_emMock->getClassMetadata(ECommerceCart::class);
        $this->collection = new PersistentCollection($this->_emMock, $classMetaData, new ArrayCollection);
        $this->collection->setInitialized(false);
        $this->collection->setOwner(new ECommerceCart(), $classMetaData->getAssociationMapping('products'));
    }

    public function testCanBePutInLazyLoadingMode()
    {
        $class = $this->_emMock->getClassMetadata(ECommerceProduct::class);
        $collection = new PersistentCollection($this->_emMock, $class, new ArrayCollection);
        $collection->setInitialized(false);
        $this->assertFalse($collection->isInitialized());
    }

    /**
     * Test that PersistentCollection::current() initializes the collection.
     */
    public function testCurrentInitializesCollection()
    {
        $this->collection->current();
        $this->assertTrue($this->collection->isInitialized());
    }

    /**
     * Test that PersistentCollection::key() initializes the collection.
     */
    public function testKeyInitializesCollection()
    {
        $this->collection->key();
        $this->assertTrue($this->collection->isInitialized());
    }

    /**
     * Test that PersistentCollection::next() initializes the collection.
     */
    public function testNextInitializesCollection()
    {
        $this->collection->next();
        $this->assertTrue($this->collection->isInitialized());
    }

    /**
     * @group DDC-3382
     */
    public function testNonObjects()
    {
        $this->assertEmpty($this->collection);

        $this->collection->add("dummy");

        $this->assertNotEmpty($this->collection);

        $product = new ECommerceProduct();

        $this->collection->set(1, $product);
        $this->collection->set(2, "dummy");
        $this->collection->set(3, null);

        $this->assertSame($product, $this->collection->get(1));
        $this->assertSame("dummy", $this->collection->get(2));
        $this->assertSame(null, $this->collection->get(3));
    }

    /**
     * @group 6110
     */
    public function testRemovingElementsAlsoRemovesKeys()
    {
        $dummy = new \stdClass();

        $this->collection->add($dummy);
        $this->assertEquals([0], array_keys($this->collection->toArray()));

        $this->collection->removeElement($dummy);
        $this->assertEquals([], array_keys($this->collection->toArray()));
    }

    /**
     * @group 6110
     */
    public function testClearWillAlsoClearKeys()
    {
        $this->collection->add(new \stdClass());
        $this->collection->clear();
        $this->assertEquals([], array_keys($this->collection->toArray()));
    }

    /**
     * @group 6110
     */
    public function testClearWillAlsoResetKeyPositions()
    {
        $dummy = new \stdClass();

        $this->collection->add($dummy);
        $this->collection->removeElement($dummy);
        $this->collection->clear();
        $this->collection->add($dummy);
        $this->assertEquals([0], array_keys($this->collection->toArray()));
    }

    /**
     * @group 6613
     * @group 6614
     * @group 6616
     */
    public function testWillKeepNewItemsInDirtyCollectionAfterInitialization() : void
    {
        /* @var $unitOfWork UnitOfWork|\PHPUnit_Framework_MockObject_MockObject */
        $unitOfWork = $this->createMock(UnitOfWork::class);

        $this->_emMock->setUnitOfWork($unitOfWork);

        $newElement       = new \stdClass();
        $persistedElement = new \stdClass();

        $this->collection->add($newElement);

        self::assertFalse($this->collection->isInitialized());
        self::assertTrue($this->collection->isDirty());

        $unitOfWork
            ->expects(self::once())
            ->method('loadCollection')
            ->with($this->collection)
            ->willReturnCallback(function (PersistentCollection $persistentCollection) use ($persistedElement) : void {
                $persistentCollection->unwrap()->add($persistedElement);
            });

        $this->collection->initialize();

        self::assertSame([$persistedElement, $newElement], $this->collection->toArray());
        self::assertTrue($this->collection->isInitialized());
        self::assertTrue($this->collection->isDirty());
    }

    /**
     * @group 6613
     * @group 6614
     * @group 6616
     */
    public function testWillDeDuplicateNewItemsThatWerePreviouslyPersistedInDirtyCollectionAfterInitialization() : void
    {
        /* @var $unitOfWork UnitOfWork|\PHPUnit_Framework_MockObject_MockObject */
        $unitOfWork = $this->createMock(UnitOfWork::class);

        $this->_emMock->setUnitOfWork($unitOfWork);

        $newElement                    = new \stdClass();
        $newElementThatIsAlsoPersisted = new \stdClass();
        $persistedElement              = new \stdClass();

        $this->collection->add($newElementThatIsAlsoPersisted);
        $this->collection->add($newElement);

        self::assertFalse($this->collection->isInitialized());
        self::assertTrue($this->collection->isDirty());

        $unitOfWork
            ->expects(self::once())
            ->method('loadCollection')
            ->with($this->collection)
            ->willReturnCallback(function (PersistentCollection $persistentCollection) use (
                $persistedElement,
                $newElementThatIsAlsoPersisted
            ) : void {
                $persistentCollection->unwrap()->add($newElementThatIsAlsoPersisted);
                $persistentCollection->unwrap()->add($persistedElement);
            });

        $this->collection->initialize();

        self::assertSame(
            [$newElementThatIsAlsoPersisted, $persistedElement, $newElement],
            $this->collection->toArray()
        );
        self::assertTrue($this->collection->isInitialized());
        self::assertTrue($this->collection->isDirty());
    }

    /**
     * @group 6613
     * @group 6614
     * @group 6616
     */
    public function testWillNotMarkCollectionAsDirtyAfterInitializationIfNoElementsWereAdded() : void
    {
        /* @var $unitOfWork UnitOfWork|\PHPUnit_Framework_MockObject_MockObject */
        $unitOfWork = $this->createMock(UnitOfWork::class);

        $this->_emMock->setUnitOfWork($unitOfWork);

        $newElementThatIsAlsoPersisted = new \stdClass();
        $persistedElement              = new \stdClass();

        $this->collection->add($newElementThatIsAlsoPersisted);

        self::assertFalse($this->collection->isInitialized());
        self::assertTrue($this->collection->isDirty());

        $unitOfWork
            ->expects(self::once())
            ->method('loadCollection')
            ->with($this->collection)
            ->willReturnCallback(function (PersistentCollection $persistentCollection) use (
                $persistedElement,
                $newElementThatIsAlsoPersisted
            ) : void {
                $persistentCollection->unwrap()->add($newElementThatIsAlsoPersisted);
                $persistentCollection->unwrap()->add($persistedElement);
            });

        $this->collection->initialize();

        self::assertSame(
            [$newElementThatIsAlsoPersisted, $persistedElement],
            $this->collection->toArray()
        );
        self::assertTrue($this->collection->isInitialized());
        self::assertFalse($this->collection->isDirty());
    }

    public function testModifyUOWForDeferredImplicitOwnerOnClear() : void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects(self::once())->method('scheduleCollectionDeletion');
        $this->_emMock->setUnitOfWork($unitOfWork);

        $this->collection->clear();
    }
}
