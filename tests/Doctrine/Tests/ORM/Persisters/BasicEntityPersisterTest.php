<?php
namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\Tests\ORM\Persisters\Collection\ExtensionOfPersistentCollection;
use Doctrine\Tests\OrmTestCase;

class BasicEntityPersisterTest extends OrmTestCase
{
    /**
     * @var BasicEntityPersister
     */
    protected $_persister;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $_em;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_em = $this->_getTestEntityManager();

        $this->_persister = new BasicEntityPersister(
            $this->_em,
            $this->_em->getClassMetadata('Doctrine\Tests\Models\GeoNames\Admin1AlternateName')
        );
    }

    /**
     * @group custom-collections
     */
    public function testLoadAllReturnTypeIsArray()
    {
        $this->_em->getConfiguration()->setResultRootType(EntityPersister::RESULT_ROOT_TYPE_ARRAY);

        $result = $this->_persister->loadAll();

        $this->assertTrue(is_array($result));
    }

    /**
     * @expectedException \Doctrine\ORM\ORMInvalidArgumentException
     *
     * @group custom-collections
     */
    public function testLoadAllThrowsExceptionIfCollectionClassIsNotSet()
    {
        $this->_em->getConfiguration()->setResultRootType(EntityPersister::RESULT_ROOT_TYPE_COLLECTION);

        $result = $this->_persister->loadAll();

        $this->assertTrue(is_array($result));
    }

    /**
     * @group custom-collections
     */
    public function testLoadAllReturnTypeIsCollection()
    {
        $this->_em->getConfiguration()->setResultRootType(EntityPersister::RESULT_ROOT_TYPE_COLLECTION);
        $this->_persister->getClassMetadata()->setCustomCollectionClass(ExtensionOfPersistentCollection::class);

        $result = $this->_persister->loadAll();

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * @expectedException \Doctrine\ORM\ORMInvalidArgumentException
     *
     * @group custom-collections
     */
    public function testLoadAllReturnTypeIsNotPersistentCollectionThrowsException()
    {
        $this->_em->getConfiguration()->setResultRootType(EntityPersister::RESULT_ROOT_TYPE_COLLECTION);
        $this->_persister->getClassMetadata()->setCustomCollectionClass(ArrayCollection::class);

        $result = $this->_persister->loadAll();

        $this->assertInstanceOf(Collection::class, $result);
    }
}
