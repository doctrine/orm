<?php


declare(strict_types=1);

namespace Doctrine\ORM\Proxy\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Proxy\Factory\Strategy\ProxyGeneratorStrategy;

class ProxyDefinitionFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ProxyResolver
     */
    private $resolver;

    /**
     * @var ProxyGeneratorStrategy
     */
    private $generatorStrategy;

    /**
     * @var int
     */
    private $autoGenerate;

    /**
     * ProxyDefinitionFactory constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ProxyResolver          $resolver
     * @param ProxyGeneratorStrategy $generatorStrategy
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ProxyResolver $resolver,
        ProxyGeneratorStrategy $generatorStrategy
    )
    {
        $this->entityManager     = $entityManager;
        $this->resolver          = $resolver;
        $this->generatorStrategy = $generatorStrategy;
    }

    /**
     * @param ClassMetadata $classMetadata
     *
     * @return ProxyDefinition
     */
    public function build(ClassMetadata $classMetadata) : ProxyDefinition
    {
        $definition = $this->createDefinition($classMetadata);

        if (! class_exists($definition->proxyClassName, false)) {
            $proxyClassPath = $this->resolver->resolveProxyClassPath($classMetadata->getClassName());

            $this->generatorStrategy->generate($proxyClassPath, $definition);
        }

        return $definition;
    }

    /**
     * @param ClassMetadata $classMetadata
     *
     * @return ProxyDefinition
     */
    private function createDefinition(ClassMetadata $classMetadata) : ProxyDefinition
    {
        $unitOfWork      = $this->entityManager->getUnitOfWork();
        $entityPersister = $unitOfWork->getEntityPersister($classMetadata->getClassName());
        $proxyClassName  = $this->resolver->resolveProxyClassName($classMetadata->getClassName());

        return new ProxyDefinition($classMetadata, $entityPersister, $proxyClassName);
    }
}
