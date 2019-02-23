<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver\Annotation\Binder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use ReflectionClass;

/**
 * Class ComponentMetadataBinder
 */
class EntityClassMetadataBinder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /** @var ReflectionClass */
    private $reflectionClass;

    /**
     * [dreaming] One day we would eliminate this and only do: $reflectionClass->getAnnotations()
     *
     * @var Annotation\Annotation[]
     */
    private $classAnnotations;

    /**
     * @todo guilhermeblanco This should disappear once we instantiation happens in the Driver
     * @var Mapping\ClassMetadata
     */
    private $classMetadata;

    /**
     * @param Annotation\Annotation[] $classAnnotations
     */
    public function __construct(
        ReflectionClass $reflectionClass,
        array $classAnnotations,
        Mapping\ClassMetadata $classMetadata,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    ) {
        $this->reflectionClass         = $reflectionClass;
        $this->classAnnotations        = $classAnnotations;
        $this->classMetadata           = $classMetadata;
        $this->metadataBuildingContext = $metadataBuildingContext;
    }

    public function bind() : Mapping\ClassMetadata
    {
        $classMetadata = $this->classMetadata;

        $this->processEntityAnnotation($classMetadata, $this->classAnnotations[Annotation\Entity::class]);

        return $classMetadata;
    }

    private function processEntityAnnotation(
        Mapping\ClassMetadata $classMetadata,
        Annotation\Entity $entityAnnotation
    ) : void {
        if ($entityAnnotation->repositoryClass !== null) {
            $repositoryClassName = $entityAnnotation->repositoryClass;

            $classMetadata->setCustomRepositoryClassName($repositoryClassName);
        }

        if ($entityAnnotation->readOnly) {
            $classMetadata->asReadOnly();
        }

        $classMetadata->isMappedSuperclass = false;
        $classMetadata->isEmbeddedClass    = false;
    }
}
