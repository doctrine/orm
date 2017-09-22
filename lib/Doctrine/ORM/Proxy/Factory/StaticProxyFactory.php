<?php


declare(strict_types=1);

namespace Doctrine\ORM\Proxy\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\TransientMetadata;
use Doctrine\ORM\Proxy\Proxy;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * Static factory for proxy objects.
 *
 * @package Doctrine\ORM\Proxy\Factory
 * @since 3.0
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class StaticProxyFactory implements ProxyFactory
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var ProxyGenerator
     */
    protected $generator;

    /**
     * @var ProxyDefinitionFactory
     */
    protected $definitionFactory;

    /**
     * @var array<string, ProxyDefinition>
     */
    private $definitions = [];

    /**
     * @var LazyLoadingGhostFactory
     */
    private $proxyFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        LazyLoadingGhostFactory $proxyFactory
    ) {
        $this->entityManager     = $entityManager;
        $this->proxyFactory      = $proxyFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @param ClassMetadata[] $classMetadataList
     */
    public function generateProxyClasses(array $classMetadataList) : int
    {
        $generated = 0;

        foreach ($classMetadataList as $classMetadata) {
            if ($classMetadata->isMappedSuperclass || $classMetadata->getReflectionClass()->isAbstract()) {
                continue;
            }

            $this
                ->proxyFactory
                ->createProxy(
                    $classMetadata->getClassName(),
                    function () {
                        // empty closure, serves its purpose, for now
                    },
                    [
                        // @TODO this should be a constant reference, not a magic constant
                        'skippedProperties' => \array_merge(
                            $this->identifierFieldFqns($classMetadata),
                            $this->transientFieldsFqns($classMetadata)
                        ),
                    ]
                );

            $generated++;
        }

        return $generated;
    }

    /**
     * {@inheritdoc}
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function getProxy(string $className, array $identifier) : GhostObjectInterface
    {
        $metadata  = $this->entityManager->getClassMetadata($className);
        $persister = $this->entityManager->getUnitOfWork()->getEntityPersister($metadata->getClassName());

        // @TODO extract the parameters passed to `createProxy` to a private cache
        $proxyInstance = $this
            ->proxyFactory
            ->createProxy(
                $metadata->getClassName(),
                function (
                    GhostObjectInterface $ghostObject,
                    string $method, // we don't care
                    array $parameters, // we don't care
                    & $initializer,
                    array $properties // we currently do not use this
                ) use ($metadata, $persister) : bool {
                    $originalInitializer = $initializer;
                    $initializer = null;

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
                },
                [
                    // @TODO this should be a constant reference, not a magic constant
                    'skippedProperties' => \array_merge(
                        $this->identifierFieldFqns($metadata),
                        $this->transientFieldsFqns($metadata)
                    ),
                ]
            );

        $persister->setIdentifier($proxyInstance, $identifier);

        return $proxyInstance;
    }

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
                    ->getProperty($name) // @TODO possible NPR. This should never be null, why is it allowed to be?
            );
        }

        return $transientFieldsFqns;
    }

    private function identifierFieldFqns(ClassMetadata $metadata) : array
    {
        $idFieldFqcns = [];

        foreach ($metadata->getIdentifierFieldNames() as $idField) {
            $property = $metadata->getProperty($idField);

            $idFieldFqcns[] = $this->propertyFqcn(
                $property
                    ->getDeclaringClass()
                    ->getReflectionClass()
                    ->getProperty($idField) // @TODO possible NPR. This should never be null, why is it allowed to be?
            );
        }

        return $idFieldFqcns;
    }

    private function propertyFqcn(\ReflectionProperty $property) : string
    {
        if ($property->isPrivate()) {
            return "\0" . $property->getDeclaringClass()->getName() . "\0" . $property->getName();
        }

        if ($property->isProtected()) {
            return "\0*\0" . $property->getName();
        }

        return $property->getName();
    }

    /**
     * @param ProxyDefinition $definition
     *
     * @return Proxy
     */
    protected function createProxyInstance(ProxyDefinition $definition) : Proxy
    {
        /** @var Proxy $classMetadata */
        $proxyClassName = $definition->proxyClassName;

        return new $proxyClassName($definition);
    }
}
