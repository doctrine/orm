<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\Tests\Models\PersistentObject\PersistentCollectionContent;
use Doctrine\Tests\Models\PersistentObject\PersistentCollectionHolder;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function class_exists;

class PersistentCollectionTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        if (! class_exists(PersistentObject::class)) {
            self::markTestSkipped('This test requires doctrine/persistence 2');
        }

        parent::setUp();

        $this->createSchemaForModels(
            PersistentCollectionHolder::class,
            PersistentCollectionContent::class,
        );

        PersistentObject::setObjectManager($this->_em);
    }

    public function testPersist(): void
    {
        $collectionHolder = new PersistentCollectionHolder();
        $content          = new PersistentCollectionContent('first element');
        $collectionHolder->addElement($content);

        $this->_em->persist($collectionHolder);
        $this->_em->flush();
        $this->_em->clear();

        $collectionHolder = $this->_em->find(PersistentCollectionHolder::class, $collectionHolder->getId());
        $collectionHolder->getCollection();

        $content = new PersistentCollectionContent('second element');
        $collectionHolder->addElement($content);

        self::assertEquals(2, $collectionHolder->getCollection()->count());
    }

    /**
     * Tests that PersistentCollection::isEmpty() does not initialize the collection when FETCH_EXTRA_LAZY is used.
     */
    public function testExtraLazyIsEmptyDoesNotInitializeCollection(): void
    {
        $collectionHolder = new PersistentCollectionHolder();

        $this->_em->persist($collectionHolder);
        $this->_em->flush();
        $this->_em->clear();

        $collectionHolder = $this->_em->find(PersistentCollectionHolder::class, $collectionHolder->getId());
        $collection       = $collectionHolder->getRawCollection();

        self::assertTrue($collection->isEmpty());
        self::assertFalse($collection->isInitialized());

        $collectionHolder->addElement(new PersistentCollectionContent());

        $this->_em->flush();
        $this->_em->clear();

        $collectionHolder = $this->_em->find(PersistentCollectionHolder::class, $collectionHolder->getId());
        $collection       = $collectionHolder->getRawCollection();

        self::assertFalse($collection->isEmpty());
        self::assertFalse($collection->isInitialized());
    }

    #[Group('#1206')]
    #[Group('DDC-3430')]
    public function testMatchingDoesNotModifyTheGivenCriteria(): void
    {
        $collectionHolder = new PersistentCollectionHolder();

        $this->_em->persist($collectionHolder);
        $this->_em->flush();
        $this->_em->clear();

        $criteria = new Criteria();

        $collectionHolder = $this->_em->find(PersistentCollectionHolder::class, $collectionHolder->getId());
        $collectionHolder->getCollection()->matching($criteria);

        self::assertEmpty($criteria->getWhereExpression());
        self::assertEmpty($criteria->getFirstResult());
        self::assertEmpty($criteria->getMaxResults());
        self::assertEmpty($criteria->getOrderings());
    }
}
