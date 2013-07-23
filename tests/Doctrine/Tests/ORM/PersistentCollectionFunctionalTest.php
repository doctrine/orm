<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Tests the initialization of persistent collections.
 * @author Austin Morris <austin.morris@gmail.com>
 */
class PersistentCollectionFunctionalTest extends OrmFunctionalTestCase
{
    /**
     * @var PersistentCollection
     */
    protected $collection;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();

        $classMetaData = $this->_em->getClassMetadata('Doctrine\Tests\Models\ECommerce\ECommerceCart');
        $this->collection = new PersistentCollection($this->_em, $classMetaData, new ArrayCollection);
        $this->collection->setInitialized(false);
        $this->collection->setOwner(new ECommerceCart(), $classMetaData->getAssociationMapping('products'));
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
}
