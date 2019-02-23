<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver\Annotation\Binder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use ReflectionClass;

class MappedSuperClassMetadataBinder
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

    /**
     * @throws Mapping\MappingException
     */
    public function bind() : Mapping\ClassMetadata
    {
        $classMetadata = $this->classMetadata;

        $this->processMappedSuperclassAnnotation($classMetadata, $this->classAnnotations[Annotation\MappedSuperclass::class]);

        return $classMetadata;
    }

    private function processMappedSuperclassAnnotation(
        Mapping\ClassMetadata $classMetadata,
        Annotation\MappedSuperclass $mappedSuperclassAnnotation
    ) : void {
        if ($mappedSuperclassAnnotation->repositoryClass !== null) {
            $classMetadata->setCustomRepositoryClassName(
                $mappedSuperclassAnnotation->repositoryClass
            );
        }

        $classMetadata->isMappedSuperclass = true;
        $classMetadata->isEmbeddedClass    = false;
    }
}
