<?php

namespace Doctrine\Tests\ORM\Functional;

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
     * @ManyToMany(targetEntity="PersistentCollectionContent", cascade={"all"})
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
