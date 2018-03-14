<?php

declare(strict_types=1);

namespace Doctrine\ORM\Decorator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Base class for EntityManager decorators
 */
abstract class EntityManagerDecorator implements EntityManagerInterface
{
    /** @var EntityManagerInterface */
    protected $wrapped;

    public function __construct(EntityManagerInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->wrapped->getConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function getExpressionBuilder()
    {
        return $this->wrapped->getExpressionBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierFlattener()
    {
        return $this->wrapped->getIdentifierFlattener();
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->wrapped->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function transactional(callable $func)
    {
        return $this->wrapped->transactional($func);
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $this->wrapped->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        $this->wrapped->rollback();
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery($dql = '')
    {
        return $this->wrapped->createQuery($dql);
    }

    /**
     * {@inheritdoc}
     */
    public function createNativeQuery($sql, ResultSetMapping $rsm)
    {
        return $this->wrapped->createNativeQuery($sql, $rsm);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder()
    {
        return $this->wrapped->createQueryBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getReference($entityName, $id)
    {
        return $this->wrapped->getReference($entityName, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getPartialReference($entityName, $identifier)
    {
        return $this->wrapped->getPartialReference($entityName, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->wrapped->close();
    }

    /**
     * {@inheritdoc}
     */
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->wrapped->lock($entity, $lockMode, $lockVersion);
    }

    /**
     * {@inheritdoc}
     */
    public function find($entityName, $id, $lockMode = null, $lockVersion = null)
    {
        return $this->wrapped->find($entityName, $id, $lockMode, $lockVersion);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->wrapped->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getEventManager()
    {
        return $this->wrapped->getEventManager();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return $this->wrapped->getConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function isOpen()
    {
        return $this->wrapped->isOpen();
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitOfWork()
    {
        return $this->wrapped->getUnitOfWork();
    }

    /**
     * {@inheritdoc}
     */
    public function getHydrator($hydrationMode)
    {
        return $this->wrapped->getHydrator($hydrationMode);
    }

    /**
     * {@inheritdoc}
     */
    public function newHydrator($hydrationMode)
    {
        return $this->wrapped->newHydrator($hydrationMode);
    }

    /**
     * {@inheritdoc}
     */
    public function getProxyFactory()
    {
        return $this->wrapped->getProxyFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return $this->wrapped->getFilters();
    }

    /**
     * {@inheritdoc}
     */
    public function isFiltersStateClean()
    {
        return $this->wrapped->isFiltersStateClean();
    }

    /**
     * {@inheritdoc}
     */
    public function hasFilters()
    {
        return $this->wrapped->hasFilters();
    }

    /**
     * {@inheritdoc}
     */
    public function getCache()
    {
        return $this->wrapped->getCache();
    }

    /**
     * {@inheritdoc}
     */
    public function persist($object)
    {
        $this->wrapped->persist($object);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($object)
    {
        $this->wrapped->remove($object);
    }

    /**
     * {@inheritdoc}
     */
    public function merge($object)
    {
        return $this->wrapped->merge($object);
    }

    /**
     * {@inheritdoc}
     */
    public function clear($objectName = null)
    {
        $this->wrapped->clear($objectName);
    }

    /**
     * {@inheritdoc}
     */
    public function detach($object)
    {
        $this->wrapped->detach($object);
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($object)
    {
        $this->wrapped->refresh($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository($className)
    {
        return $this->wrapped->getRepository($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getClassMetadata($className)
    {
        return $this->wrapped->getClassMetadata($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFactory()
    {
        return $this->wrapped->getMetadataFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function initializeObject($obj)
    {
        $this->wrapped->initializeObject($obj);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($object)
    {
        return $this->wrapped->contains($object);
    }
}
