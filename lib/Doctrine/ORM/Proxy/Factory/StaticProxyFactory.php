<?php

declare(strict_types=1);

namespace Doctrine\ORM\Proxy\Factory;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\TransientMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\Proxy\GhostObjectInterface;
use ReflectionProperty;
use function array_filter;
use function array_merge;
use function count;

/**
 * Static factory for proxy objects.
 *
 * @internal this class is to be used by ORM internals only
 */
final class StaticProxyFactory implements ProxyFactory
{
    private const SKIPPED_PROPERTIES = 'skippedProperties';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var LazyLoadingGhostFactory */
    private $proxyFactory;

    /** @var Closure[] indexed by metadata class name */
    private $cachedInitializers = [];

    /** @var EntityPersister[] indexed by metadata class name */
    private $cachedPersisters = [];

    /** @var string[][][] indexed by metadata class name */
    private $cachedSkippedProperties = [];

    public function __construct(
        EntityManagerInterface $entityManager,
        LazyLoadingGhostFactory $proxyFactory
    ) {
        $this->entityManager = $entityManager;
        $this->proxyFactory  = $proxyFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @param ClassMetadata[] $classes
     */
    public function generateProxyClasses(array $classes) : int
    {
        $concreteClasses = array_filter($classes, static function (ClassMetadata $metadata) : bool {
            return ! ($metadata->isMappedSuperclass || $metadata->getReflectionClass()->isAbstract());
        });

        foreach ($concreteClasses as $metadata) {
            $this
                ->proxyFactory
                ->createProxy(
                    $metadata->getClassName(),
                    static function () {
                        // empty closure, serves its purpose, for now
                    },
                    $this->skippedFieldsFqns($metadata)
                );
        }

        return count($concreteClasses);
    }

    /**
     * {@inheritdoc}
     *
     * @throws EntityNotFoundException
     */
    public function getProxy(ClassMetadata $metadata, array $identifier) : GhostObjectInterface
    {
        $className                                 = $metadata->getClassName();
        $persister                                 = $this->cachedPersisters[$className]
            ?? $this->cachedPersisters[$className] = $this
                ->entityManager
                ->getUnitOfWork()
                ->getEntityPersister($metadata->getClassName());

        $proxyInstance                                            = $this
            ->proxyFactory
            ->createProxy(
                $metadata->getClassName(),
                $this->cachedInitializers[$className]
                    ?? $this->cachedInitializers[$className]      = $this->makeInitializer($metadata, $persister),
                $this->cachedSkippedProperties[$className]
                    ?? $this->cachedSkippedProperties[$className] = [
                        self::SKIPPED_PROPERTIES => $this->skippedFieldsFqns($metadata),
                    ]
            );

        $persister->setIdentifier($proxyInstance, $identifier);

        return $proxyInstance;
    }

    private function makeInitializer(ClassMetadata $metadata, EntityPersister $persister) : Closure
    {
        return static function (
            GhostObjectInterface $ghostObject,
            string $method,
            // we don't care
            array $parameters,
            // we don't care
            & $initializer,
            array $properties // we currently do not use this
        ) use (
            $metadata,
            $persister
) : bool {
            $originalInitializer = $initializer;
            $initializer         = null;

            $identifier = $persister->getIdentifier($ghostObject);

            // @TODO how do we use `$properties` in the persister? That would be a massive optimisation
            if (! $persister->loadById($identifier, $ghostObject)) {
                $initializer = $originalInitializer;

                throw EntityNotFoundException::fromClassNameAndIdentifier(
                    $metadata->getClassName(),
                    $identifier
                );
            }

            return true;
        };
    }

    /**
     * @return string[]
     */
    private function skippedFieldsFqns(ClassMetadata $metadata) : array
    {
        return array_merge(
            $this->identifierFieldFqns($metadata),
            $this->transientFieldsFqns($metadata)
        );
    }

    /**
     * @return string[]
     */
    private function transientFieldsFqns(ClassMetadata $metadata) : array
    {
        $transientFieldsFqns = [];

        foreach ($metadata->getDeclaredPropertiesIterator() as $name => $property) {
            if (! $property instanceof TransientMetadata) {
                continue;
            }

            $transientFieldsFqns[] = $this->propertyFqcn(
                $property
                    ->getDeclaringClass()
                    ->getReflectionClass()
                    ->getProperty($name) // @TODO possible NPE. This should never be null, why is it allowed to be?
            );
        }

        return $transientFieldsFqns;
    }

    /**
     * @return string[]
     */
    private function identifierFieldFqns(ClassMetadata $metadata) : array
    {
        $idFieldFqcns = [];

        foreach ($metadata->getIdentifierFieldNames() as $idField) {
            $idFieldFqcns[] = $this->propertyFqcn(
                $metadata
                    ->getProperty($idField)
                    ->getDeclaringClass()
                    ->getReflectionClass()
                    ->getProperty($idField) // @TODO possible NPE. This should never be null, why is it allowed to be?
            );
        }

        return $idFieldFqcns;
    }

    private function propertyFqcn(ReflectionProperty $property) : string
    {
        if ($property->isPrivate()) {
            return "\0" . $property->getDeclaringClass()->getName() . "\0" . $property->getName();
        }

        if ($property->isProtected()) {
            return "\0*\0" . $property->getName();
        }

        return $property->getName();
    }
}
