<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\PersistentObject;

/**
 */
class PersistentCollectionTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\PersistentCollectionHolder'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\PersistentCollectionContent'),
            ));
        } catch (\Exception $e) {

        }
        PersistentObject::setObjectManager($this->_em);
    }

    public function testPersist()
    {
        $collectionHolder = new PersistentCollectionHolder();
        $content = new PersistentCollectionContent('first element');
        $collectionHolder->addElement($content);

        $this->_em->persist($collectionHolder);
        $this->_em->flush();
        $this->_em->clear();

        $collectionHolder = $this->_em->find(__NAMESPACE__ . '\PersistentCollectionHolder', $collectionHolder->getId());
        $collectionHolder->getCollection();

        $content = new PersistentCollectionContent('second element');
        $collectionHolder->addElement($content);

        $this->assertEquals(2, $collectionHolder->getCollection()->count());
    }

    /**
     * Tests that PersistentCollection::isEmpty() does not initialize the collection when FETCH_EXTRA_LAZY is used.
     */
    public function testExtraLazyIsEmptyDoesNotInitializeCollection()
    {
        $collectionHolder = new PersistentCollectionHolder();

        $this->_em->persist($collectionHolder);
        $this->_em->flush();
        $this->_em->clear();

        $collectionHolder = $this->_em->find(__NAMESPACE__ . '\PersistentCollectionHolder', $collectionHolder->getId());
        $collection = $collectionHolder->getRawCollection();

        $this->assertTrue($collection->isEmpty());
        $this->assertFalse($collection->isInitialized());

        $collectionHolder->addElement(new PersistentCollectionContent());

        $this->_em->flush();
        $this->_em->clear();

        $collectionHolder = $this->_em->find(__NAMESPACE__ . '\PersistentCollectionHolder', $collectionHolder->getId());
        $collection = $collectionHolder->getRawCollection();

        $this->assertFalse($collection->isEmpty());
        $this->assertFalse($collection->isInitialized());
    }

    /**
     * @group #1206
     * @group DDC-3430
     */
    public function testMatchingDoesNotModifyTheGivenCriteria()
    {
        $collectionHolder = new PersistentCollectionHolder();

        $this->_em->persist($collectionHolder);
        $this->_em->flush();
        $this->_em->clear();

        $criteria = new Criteria();

        $collectionHolder = $this->_em->find(__NAMESPACE__ . '\PersistentCollectionHolder', $collectionHolder->getId());
        $collectionHolder->getCollection()->matching($criteria);

        $this->assertEmpty($criteria->getWhereExpression());
        $this->assertEmpty($criteria->getFirstResult());
        $this->assertEmpty($criteria->getMaxResults());
        $this->assertEmpty($criteria->getOrderings());
    }
}

/**
 * @Entity
 */
class PersistentCollectionHolder extends PersistentObject
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ManyToMany(targetEntity="PersistentCollectionContent", cascade={"all"}, fetch="EXTRA_LAZY")
     */
    protected $collection;

    public function __construct()
    {
        $this->collection = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @param PersistentCollectionContent $element
     */
    public function addElement(PersistentCollectionContent $element)
    {
        $this->collection->add($element);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCollection()
    {
        return clone $this->collection;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRawCollection()
    {
        return $this->collection;
    }
}

/**
 * @Entity
 */
class PersistentCollectionContent extends PersistentObject
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;
}
