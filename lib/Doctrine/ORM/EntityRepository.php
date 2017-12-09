<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Common\Collections\Criteria;

/**
 * An EntityRepository serves as a repository for entities with generic as well as
 * business specific methods for retrieving entities.
 *
 * This class is designed for inheritance and users can subclass this class to
 * write their own repositories with business-specific methods to locate entities.
 *
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class EntityRepository implements EntityRepositoryInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $class;

    /**
     * Initializes a new <tt>EntityRepository</tt>.
     *
     * @param EntityManagerInterface $em    The EntityManager to use.
     * @param Mapping\ClassMetadata  $class The class descriptor.
     */
    public function __construct(EntityManagerInterface $em, Mapping\ClassMetadata $class)
    {
        $this->entityName = $class->getClassName();
        $this->em         = $em;
        $this->class      = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder($alias, $indexBy = null) : QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->from($this->entityName, $alias, $indexBy);
    }

    /**
     * {@inheritdoc}
     */
    public function createResultSetMappingBuilder($alias) : ResultSetMappingBuilder
    {
        $rsm = new ResultSetMappingBuilder($this->em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);
        $rsm->addRootEntityFromClassMetadata($this->entityName, $alias);

        return $rsm;
    }

    /**
     * {@inheritdoc}
     */
    public function createNamedQuery($queryName) : Query
    {
        $namedQuery    = $this->class->getNamedQuery($queryName);
        $resolvedQuery = str_replace('__CLASS__', $this->class->getClassName(), $namedQuery);

        return $this->em->createQuery($resolvedQuery);
    }

    /**
     * {@inheritdoc}
     */
    public function createNativeNamedQuery($queryName) : NativeQuery
    {
        $queryMapping = $this->class->getNamedNativeQuery($queryName);
        $rsm          = new Query\ResultSetMappingBuilder($this->em);

        $rsm->addNamedNativeQueryMapping($this->class, $queryMapping);

        return $this->em->createNativeQuery($queryMapping['query'], $rsm);
    }

    /**
     * {@inheritdoc}
     */
    public function clear() : void
    {
        $this->em->clear($this->class->getRootClassName());
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null) : ?object
    {
        return $this->em->find($this->entityName, $id, $lockMode, $lockVersion);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll() : array
    {
        return $this->findBy([]);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null) : array
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->entityName);

        return $persister->loadAll($criteria, $orderBy !== null ? $orderBy : [], $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria, array $orderBy = []) : ?object
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->entityName);

        return $persister->load($criteria, null, null, [], null, 1, $orderBy);
    }

    /**
     * {@inheritdoc}
     */
    public function count(array $criteria) : int
    {
        return $this->em->getUnitOfWork()->getEntityPersister($this->entityName)->count($criteria);
    }

    /**
     * Adds support for magic method calls.
     *
     * @param string  $method
     * @param mixed[] $arguments
     *
     * @return mixed The returned value from the resolved method.
     *
     * @throws ORMException
     * @throws \BadMethodCallException If the method called is invalid
     */
    public function __call(string $method, array $arguments)
    {
        if (0 === strpos($method, 'findBy')) {
            return $this->resolveMagicCall('findBy', substr($method, 6), $arguments);
        }

        if (0 === strpos($method, 'findOneBy')) {
            return $this->resolveMagicCall('findOneBy', substr($method, 9), $arguments);
        }

        if (0 === strpos($method, 'countBy')) {
            return $this->resolveMagicCall('count', substr($method, 7), $arguments);
        }

        throw new \BadMethodCallException(
            "Undefined method '$method'. The method name must start with ".
            "either findBy, findOneBy or countBy!"
        );
    }

    protected function getEntityName() : string
    {
        return $this->entityName;
    }

    public function getClassName() : string
    {
        return $this->getEntityName();
    }

    protected function getEntityManager() : EntityManagerInterface
    {
        return $this->em;
    }

    protected function getClassMetadata() : ClassMetadata
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function matching(Criteria $criteria) : Collection
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->entityName);

        return new LazyCriteriaCollection($persister, $criteria);
    }

    /**
     * Resolves a magic method call to the proper existent method at `EntityRepository`.
     *
     * @param string  $method    The method to call
     * @param string  $by        The property name used as condition
     * @param mixed[] $arguments The arguments to pass at method call
     *
     * @throws ORMException If the method called is invalid or the requested field/association does not exist
     *
     * @return mixed
     */
    private function resolveMagicCall(string $method, string $by, array $arguments)
    {
        if (! $arguments) {
            throw ORMException::findByRequiresParameter($method . $by);
        }

        $fieldName = lcfirst(Inflector::classify($by));

        if (null === $this->class->getProperty($fieldName)) {
            throw ORMException::invalidMagicCall($this->entityName, $fieldName, $method . $by);
        }

        return $this->$method([$fieldName => $arguments[0]], ...array_slice($arguments, 1));
    }
}
