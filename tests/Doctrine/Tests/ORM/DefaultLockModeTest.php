<?php

namespace Doctrine\Tests\ORM;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Tests\OrmTestCase;

require_once __DIR__ . '/../TestInit.php';

/**
 * Tests that the default lock mode is correctly applied on all queries.
 */
class DefaultLockModeTest extends OrmTestCase
{
    const ENTITY_CLASS = 'Doctrine\Tests\Models\Taxi\Car';
    const DQL_QUERY    = 'SELECT c FROM Doctrine\Tests\Models\Taxi\Car c WHERE c.brand = :brand';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Doctrine\ORM\Configuration
     */
    private $configuration;

    /**
     * @var \Doctrine\Tests\Mocks\DriverConnectionMock
     */
    private $driverConnection;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->em = $this->_getTestEntityManager();
        $this->configuration = $this->em->getConfiguration();
        $this->driverConnection = $this->em->getConnection()->getWrappedConnection();
        $this->em->beginTransaction();
    }

    /**
     * @param boolean     $hasLock Whether the SQL query should have a pessimistic lock.
     * @param string|null $query   The SQL query, or null to use the last prepared statement SQL.
     */
    private function assertQueryHasLock($hasLock, $query = null)
    {
        if ($query === null) {
            $query = $this->driverConnection->getLastPreparedStatementSql();
        }

        $this->assertStringEndsWith($hasLock ? ' FOR UPDATE' : ' = ?', $query);
    }

    public function testNewEntityManagerHasNoDefaultLockMode()
    {
        $this->assertNull($this->configuration->getDefaultLockMode());
    }

    public function testSetAndGetDefaultLockMode()
    {
        $this->configuration->setDefaultLockMode(LockMode::PESSIMISTIC_WRITE);
        $this->assertSame(LockMode::PESSIMISTIC_WRITE, $this->configuration->getDefaultLockMode());
    }

    public function testEntityManagerFindUsesDefaultLockMode()
    {
        $this->configuration->setDefaultLockMode(LockMode::PESSIMISTIC_WRITE);
        $this->em->find(self::ENTITY_CLASS, '');
        $this->assertQueryHasLock(true);
    }

    public function testEntityManagerFindWithLockModeOverridesDefaultLockMode()
    {
        $this->configuration->setDefaultLockMode(LockMode::PESSIMISTIC_WRITE);
        $this->em->find(self::ENTITY_CLASS, '', LockMode::NONE);
        $this->assertQueryHasLock(false);
    }

    public function testEntityRepositoryFindByUsesDefaultLockMode()
    {
        $this->configuration->setDefaultLockMode(LockMode::PESSIMISTIC_WRITE);
        $this->em->getRepository(self::ENTITY_CLASS)->findBy(array('model' => 'model'));
        $this->assertQueryHasLock(true);
    }

    public function testDqlQueryUsesDefaultLockMode()
    {
        $this->configuration->setDefaultLockMode(LockMode::PESSIMISTIC_WRITE);
        $query = $this->em->createQuery(self::DQL_QUERY);
        $query->setParameter('brand', 'brand');
        $this->assertQueryHasLock(true, $query->getSQL());
    }

    public function testDqlQueryWithLockModeOverridesDefaultLockMode()
    {
        $this->configuration->setDefaultLockMode(LockMode::PESSIMISTIC_WRITE);
        $query = $this->em->createQuery(self::DQL_QUERY);
        $query->setParameter('brand', 'brand');
        $query->setLockMode(LockMode::NONE);
        $this->assertQueryHasLock(false, $query->getSQL());
    }

    public function testProxyUsesDefaultLockMode()
    {
        $this->configuration->setDefaultLockMode(LockMode::PESSIMISTIC_WRITE);
        $proxy = $this->em->getReference(self::ENTITY_CLASS, 1);
        try {
            $proxy->__load();
        } catch (EntityNotFoundException $e) {
            // The entity is expected not to be found.
        }
        $this->assertQueryHasLock(true);
    }

    public function testLazyLoadedCollectionUsesDefaultLockMode()
    {
        $this->configuration->setDefaultLockMode(LockMode::PESSIMISTIC_WRITE);
        $entity = $this->em->getUnitOfWork()->createEntity(self::ENTITY_CLASS, array(
            'brand' => 'brand',
            'model' => 'model'
        ));
        $entity->getCarRides()->toArray();
        $this->assertQueryHasLock(true);
    }
}
