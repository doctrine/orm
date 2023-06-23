<?php

declare(strict_types=1);

namespace Doctrine\ORM\Decorator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ObjectManagerDecorator;

use function func_get_arg;
use function func_num_args;
use function get_debug_type;
use function method_exists;
use function sprintf;
use function trigger_error;

use const E_USER_NOTICE;

/**
 * Base class for EntityManager decorators
 *
 * @extends ObjectManagerDecorator<EntityManagerInterface>
 */
abstract class EntityManagerDecorator extends ObjectManagerDecorator implements EntityManagerInterface
{
    public function __construct(EntityManagerInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * {@inheritDoc}
     */
    public function getConnection()
    {
        return $this->wrapped->getConnection();
    }

    /**
     * {@inheritDoc}
     */
    public function getExpressionBuilder()
    {
        return $this->wrapped->getExpressionBuilder();
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-param class-string<T> $className
     *
     * @psalm-return EntityRepository<T>
     *
     * @template T of object
     */
    public function getRepository($className)
    {
        return $this->wrapped->getRepository($className);
    }

    /**
     * {@inheritDoc}
     */
    public function getClassMetadata($className)
    {
        return $this->wrapped->getClassMetadata($className);
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        $this->wrapped->beginTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function transactional($func)
    {
        return $this->wrapped->transactional($func);
    }

    /**
     * {@inheritDoc}
     */
    public function wrapInTransaction(callable $func)
    {
        if (! method_exists($this->wrapped, 'wrapInTransaction')) {
            trigger_error(
                sprintf('Calling `transactional()` instead of `wrapInTransaction()` which is not implemented on %s', get_debug_type($this->wrapped)),
                E_USER_NOTICE
            );

            return $this->wrapped->transactional($func);
        }

        return $this->wrapped->wrapInTransaction($func);
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        $this->wrapped->commit();
    }

    /**
     * {@inheritDoc}
     */
    public function rollback()
    {
        $this->wrapped->rollback();
    }

    /**
     * {@inheritDoc}
     */
    public function createQuery($dql = '')
    {
        return $this->wrapped->createQuery($dql);
    }

    /**
     * {@inheritDoc}
     */
    public function createNamedQuery($name)
    {
        return $this->wrapped->createNamedQuery($name);
    }

    /**
     * {@inheritDoc}
     */
    public function createNativeQuery($sql, ResultSetMapping $rsm)
    {
        return $this->wrapped->createNativeQuery($sql, $rsm);
    }

    /**
     * {@inheritDoc}
     */
    public function createNamedNativeQuery($name)
    {
        return $this->wrapped->createNamedNativeQuery($name);
    }

    /**
     * {@inheritDoc}
     */
    public function createQueryBuilder()
    {
        return $this->wrapped->createQueryBuilder();
    }

    /**
     * {@inheritDoc}
     */
    public function getReference($entityName, $id)
    {
        return $this->wrapped->getReference($entityName, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function getPartialReference($entityName, $identifier)
    {
        return $this->wrapped->getPartialReference($entityName, $identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        $this->wrapped->close();
    }

    /**
     * {@inheritDoc}
     */
    public function copy($entity, $deep = false)
    {
        return $this->wrapped->copy($entity, $deep);
    }

    /**
     * {@inheritDoc}
     */
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->wrapped->lock($entity, $lockMode, $lockVersion);
    }

    /**
     * {@inheritDoc}
     */
    public function find($className, $id, $lockMode = null, $lockVersion = null)
    {
        return $this->wrapped->find($className, $id, $lockMode, $lockVersion);
    }

    /**
     * {@inheritDoc}
     */
    public function flush($entity = null)
    {
        $this->wrapped->flush($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function refresh($object)
    {
        $lockMode = null;

        if (func_num_args() > 1) {
            $lockMode = func_get_arg(1);
        }

        $this->wrapped->refresh($object, $lockMode);
    }

    /**
     * {@inheritDoc}
     */
    public function getEventManager()
    {
        return $this->wrapped->getEventManager();
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration()
    {
        return $this->wrapped->getConfiguration();
    }

    /**
     * {@inheritDoc}
     */
    public function isOpen()
    {
        return $this->wrapped->isOpen();
    }

    /**
     * {@inheritDoc}
     */
    public function getUnitOfWork()
    {
        return $this->wrapped->getUnitOfWork();
    }

    /**
     * {@inheritDoc}
     */
    public function getHydrator($hydrationMode)
    {
        return $this->wrapped->getHydrator($hydrationMode);
    }

    /**
     * {@inheritDoc}
     */
    public function newHydrator($hydrationMode)
    {
        return $this->wrapped->newHydrator($hydrationMode);
    }

    /**
     * {@inheritDoc}
     */
    public function getProxyFactory()
    {
        return $this->wrapped->getProxyFactory();
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return $this->wrapped->getFilters();
    }

    /**
     * {@inheritDoc}
     */
    public function isFiltersStateClean()
    {
        return $this->wrapped->isFiltersStateClean();
    }

    /**
     * {@inheritDoc}
     */
    public function hasFilters()
    {
        return $this->wrapped->hasFilters();
    }

    /**
     * {@inheritDoc}
     */
    public function getCache()
    {
        return $this->wrapped->getCache();
    }
}
