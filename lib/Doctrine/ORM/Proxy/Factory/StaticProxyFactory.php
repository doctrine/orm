<?php


declare(strict_types=1);

namespace Doctrine\ORM\Proxy\Factory;

use Doctrine\ORM\Configuration\ProxyConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Proxy\Proxy;
use ProxyManager\Configuration;
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
     * ProxyFactory constructor.
     *
     * @param ProxyConfiguration $configuration
     */
    public function __construct(EntityManagerInterface $entityManager, ProxyConfiguration $configuration)
    {
        $resolver          = $configuration->getResolver();
        //$autoGenerate      = $configuration->getAutoGenerate();
        $generator         = new ProxyGenerator();
        $generatorStrategy = new Strategy\ConditionalFileWriterProxyGeneratorStrategy($generator);
        $definitionFactory = new ProxyDefinitionFactory($entityManager, $resolver, $generatorStrategy);

        $generator->setPlaceholder('baseProxyInterface', GhostObjectInterface::class);

        $this->entityManager     = $entityManager;
        $this->definitionFactory = $definitionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function generateProxyClasses(array $classMetadataList) : int
    {
        $generated = 0;

        foreach ($classMetadataList as $classMetadata) {
            if ($classMetadata->isMappedSuperclass || $classMetadata->getReflectionClass()->isAbstract()) {
                continue;
            }

            $this->definitionFactory->build($classMetadata);

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
        $metadata = $this->entityManager->getClassMetadata($className);

        return (new LazyLoadingGhostFactory(new Configuration()))
            ->createProxy(
                $metadata->getClassName(),
                function (
                    GhostObjectInterface $ghostObject,
                    string $method, // we don't care
                    array $parameters, // we don't care
                    & $initializer,
                    array $properties
                ) use ($metadata) : bool {
                    $originalInitializer = $initializer;
                    $initializer = null;

                    $persister = $this->entityManager->getUnitOfWork()->getEntityPersister($metadata->getClassName());

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
                    'skippedProperties' => $this->identifierFieldFqns($metadata)
                ]
            );
        $proxyDefinition = $this->getOrCreateProxyDefinition($className);
        $proxyInstance   = $this->createProxyInstance($proxyDefinition);
        $proxyPersister  = $proxyDefinition->entityPersister;

        $proxyPersister->setIdentifier($proxyInstance, $identifier);

        return $proxyInstance;
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

    /**
     * Create a proxy definition for the given class name.
     *
     * @param string $className
     *
     * @return ProxyDefinition
     */
    private function getOrCreateProxyDefinition(string $className) : ProxyDefinition
    {
        if (! isset($this->definitions[$className])) {
            $classMetadata = $this->entityManager->getClassMetadata($className);

            $this->definitions[$className] = $this->definitionFactory->build($classMetadata);
        }

        return $this->definitions[$className];
    }
}
