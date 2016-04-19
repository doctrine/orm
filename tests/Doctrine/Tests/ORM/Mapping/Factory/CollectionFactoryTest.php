<?php
namespace Doctrine\Tests\ORM\Mapping\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Factory\CollectionFactory;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\ORM\Persisters\Collection\ExtensionOfPersistentCollection;
use Doctrine\Tests\OrmTestCase;

class CollectionFactoryTest extends OrmTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $_em;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ClassMetadata
     */
    protected $classMetadata;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_em = $this->_getTestEntityManager();

        $this->classMetadata = $this->_em->getClassMetadata('Doctrine\Tests\Models\GeoNames\Admin1AlternateName');

        $this->collectionFactory = new CollectionFactory();
    }

    /**
     * @group custom-collections
     */
    public function testCollectionFactoryInstantiatesPersistentCollectionIfCollectionTypeIsNotSet()
    {
        $result = $this->collectionFactory->create(
            $this->_em,
            $this->classMetadata,
            new ArrayCollection([])
        );

        $this->assertInstanceOf(PersistentCollection::class, $result);
    }

    /**
     * @group custom-collections
     */
    public function testCollectionFactoryInstantiatesTheRequiredCollection()
    {
        $this->classMetadata->setCustomCollectionClass(ExtensionOfPersistentCollection::class);

        $result = $this->collectionFactory->create(
            $this->_em,
            $this->classMetadata,
            new ArrayCollection([])
        );

        $this->assertInstanceOf(ExtensionOfPersistentCollection::class, $result);
    }

    /**
     * @expectedException \Doctrine\ORM\ORMInvalidArgumentException
     *
     * @group custom-collections
     */
    public function testCollectionFactoryThrowsExceptionIfCollectionTypeIsNotPersistentCollection()
    {
        $this->classMetadata->setCustomCollectionClass(ArrayCollection::class);

        $this->collectionFactory->create(
            $this->_em,
            $this->classMetadata,
            new ArrayCollection([])
        );
    }
}
