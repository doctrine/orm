<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Factory\Strategy\ClassMetadataGeneratorStrategy;
use function class_exists;

class ClassMetadataDefinitionFactory
{
    /** @var ClassMetadataResolver */
    private $resolver;

    /** @var ClassMetadataGeneratorStrategy */
    private $generatorStrategy;

    public function __construct(ClassMetadataResolver $resolver, ClassMetadataGeneratorStrategy $generatorStrategy)
    {
        $this->resolver          = $resolver;
        $this->generatorStrategy = $generatorStrategy;
    }

    /**
     * @param string $className
     * @param ClassMetadata|null $parentMetadata
     * @param ClassMetadataBuildingContext $metadataBuildingContext
     *
     * @return ClassMetadataDefinition
     */
    public function build(
        string $className,
        ?ClassMetadata $parentMetadata,
        ClassMetadataBuildingContext $metadataBuildingContext
    ) : ClassMetadataDefinition
    {
        $definition = $this->createDefinition($className, $parentMetadata);

        if (! class_exists($definition->metadataClassName, false)) {
            $metadataClassPath = $this->resolver->resolveMetadataClassPath($className);

            $this->generatorStrategy->generate($metadataClassPath, $definition, $metadataBuildingContext);
        }

        return $definition;
    }

    /**
     * @param string $className
     * @param ClassMetadata|null $parentMetadata
     *
     * @return ClassMetadataDefinition
     */
    private function createDefinition(string $className, ?ClassMetadata $parentMetadata) : ClassMetadataDefinition
    {
        $metadataClassName = $this->resolver->resolveMetadataClassName($className);

        return new ClassMetadataDefinition($className, $metadataClassName, $parentMetadata);
    }
}
