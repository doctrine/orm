<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\PersistentObject;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

class PersistentCollectionTest extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema([
                $this->em->getClassMetadata(PersistentCollectionHolder::class),
                $this->em->getClassMetadata(PersistentCollectionContent::class),
            ]);
        } catch (Exception $e) {
        }

        PersistentObject::setEntityManager($this->em);
    }

    public function testPersist() : void
    {
        $collectionHolder = new PersistentCollectionHolder();
        $content          = new PersistentCollectionContent('first element');
        $collectionHolder->addElement($content);

        $this->em->persist($collectionHolder);
        $this->em->flush();
        $this->em->clear();

        $collectionHolder = $this->em->find(PersistentCollectionHolder::class, $collectionHolder->getId());
        $collectionHolder->getCollection();

        $content = new PersistentCollectionContent('second element');
        $collectionHolder->addElement($content);

        self::assertEquals(2, $collectionHolder->getCollection()->count());
    }

    /**
     * Tests that PersistentCollection::isEmpty() does not initialize the collection when FetchMode::EXTRA_LAZY is used.
     */
    public function testExtraLazyIsEmptyDoesNotInitializeCollection() : void
    {
        $collectionHolder = new PersistentCollectionHolder();

        $this->em->persist($collectionHolder);
        $this->em->flush();
        $this->em->clear();

        $collectionHolder = $this->em->find(PersistentCollectionHolder::class, $collectionHolder->getId());
        $collection       = $collectionHolder->getRawCollection();

        self::assertTrue($collection->isEmpty());
        self::assertFalse($collection->isInitialized());

        $collectionHolder->addElement(new PersistentCollectionContent());

        $this->em->flush();
        $this->em->clear();

        $collectionHolder = $this->em->find(PersistentCollectionHolder::class, $collectionHolder->getId());
        $collection       = $collectionHolder->getRawCollection();

        self::assertFalse($collection->isEmpty());
        self::assertFalse($collection->isInitialized());
    }

    /**
     * @group #1206
     * @group DDC-3430
     */
    public function testMatchingDoesNotModifyTheGivenCriteria() : void
    {
        $collectionHolder = new PersistentCollectionHolder();

        $this->em->persist($collectionHolder);
        $this->em->flush();
        $this->em->clear();

        $criteria = new Criteria();

        $collectionHolder = $this->em->find(PersistentCollectionHolder::class, $collectionHolder->getId());
        $collectionHolder->getCollection()->matching($criteria);

        self::assertEmpty($criteria->getWhereExpression());
        self::assertEmpty($criteria->getFirstResult());
        self::assertEmpty($criteria->getMaxResults());
        self::assertEmpty($criteria->getOrderings());
    }
}

/**
 * @ORM\Entity
 */
class PersistentCollectionHolder extends PersistentObject
{
    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToMany(targetEntity=PersistentCollectionContent::class, cascade={"all"}, fetch="EXTRA_LAZY")
     *
     * @var Collection
     */
    protected $collection;

    public function __construct()
    {
        $this->collection = new ArrayCollection();
    }

    public function addElement(PersistentCollectionContent $element)
    {
        $this->collection->add($element);
    }

    /**
     * @return Collection
     */
    public function getCollection()
    {
        return clone $this->collection;
    }

    /**
     * @return Collection
     */
    public function getRawCollection()
    {
        return $this->collection;
    }
}

/**
 * @ORM\Entity
 */
class PersistentCollectionContent extends PersistentObject
{
    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;
}
