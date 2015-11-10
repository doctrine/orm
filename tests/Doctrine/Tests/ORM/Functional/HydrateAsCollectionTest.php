<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Query;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator;
use Doctrine\ORM\Internal\Hydration\SimpleObjectHydrator;
use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\Tests\Models\CMS\CmsUser;

class HydrateAsCollectionTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * @var CustomEntityManager
     */
    protected $_cem;

    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
        
        $this->_cem = CustomEntityManager::fromEm($this->_em);
    }

    public function testLoadAsCollections()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';
        $this->_cem->persist($user);
        $this->_cem->flush();
        
        $userId = $user->getId();
        
        $this->_cem->clear();
        
        $repos = $this->_cem->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        
        $user = $repos->find($userId);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $user);

        // Test for DDC-3987
        $user = $repos->find(9001);
        $this->assertNull($user);
    }

}

/**
 * Simulate an array collection class from other projects that
 * has a different implementation on the ArrayAccess methods.
 *
 * {@inheritDoc}
 */
class SomeArrayCollection extends ArrayCollection
{
    /**
     * Explicitly exclude the safetycheck from ArrayCollection::get().
     *
     * {@inheritDoc}
     */
    public function offsetGet($key)
    {
        $elements = $this->toArray();
        return $elements[$key];
    }
}

class ObjectsToCollectionHydrator extends ObjectHydrator
{
    /**
     * Hydrates all rows from the current statement instance at once.
     *
     * {@inheritDoc}
     *
     * @return SomeArrayCollection
     */
    protected function hydrateAllData()
    {
        return new SomeArrayCollection(
            parent::hydrateAllData()
        );
    }
}

class SimpleObjectsToCollectionHydrator extends SimpleObjectHydrator
{
    /**
     * Hydrates all rows from the current statement instance at once.
     *
     * {@inheritDoc}
     *
     * @return SomeArrayCollection
     */
    protected function hydrateAllData()
    {
        return new SomeArrayCollection(
            parent::hydrateAllData()
        );
    }
}

class CustomEntityManager extends \Doctrine\ORM\EntityManager {
    /**
     * {@inheritDoc}
     */
    protected function __construct(\Doctrine\DBAL\Connection $conn,
        \Doctrine\ORM\Configuration $config,
        \Doctrine\Common\EventManager $eventManager)
    {
        parent::__construct($conn, $config, $eventManager);
    }

    /**
     * Returns SomeArrayCollection for the hydration modes
     * HYDRATE_OBJECT and HYDRATE_SIMPLEOBJECT.
     *
     * {@inheritDoc}
     */
    public function newHydrator($hydrationMode)
    {
        switch ($hydrationMode) {
            case Query::HYDRATE_OBJECT:
                return new ObjectsToCollectionHydrator($this);

            case Query::HYDRATE_SIMPLEOBJECT:
                return new SimpleObjectsToCollectionHydrator($this);
        }

        return parent::newHydrator($hydrationMode);
    }

    /**
     * Create an instance of CustomEntityManager based on another
     * EntityManager instance.
     *
     * @return CustomEntityManager
     */
    public static function fromEm(\Doctrine\ORM\EntityManager $em)
    {
        return new self($em->getConnection(), $em->getConfiguration(), $em->getEventManager());
    }

}
