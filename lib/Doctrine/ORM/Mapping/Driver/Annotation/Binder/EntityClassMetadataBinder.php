<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver\Annotation\Binder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;

/**
 * Class ComponentMetadataBinder
 *
 * @package Doctrine\ORM\Mapping\Driver\Annotation\Binder
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class EntityClassMetadataBinder
{
    /**
     * @var Mapping\ClassMetadataBuildingContext
     */
    private $metadataBuildingContext;

    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;

    /**
     * [dreaming] One day we would eliminate this and only do: $reflectionClass->getAnnotations()
     *
     * @var array<string, object>
     */
    private $classAnnotations;

    /**
     * @todo guilhermeblanco This should disappear once we instantiation happens in the Driver
     *
     * @var Mapping\ClassMetadata
     */
    private $classMetadata;

    /**
     * ComponentMetadataBinder constructor.
     *
     * @param \ReflectionClass                     $reflectionClass
     * @param array<string, object>                $classAnnotations
     * @param Mapping\ClassMetadata                $classMetadata
     * @param Mapping\ClassMetadataBuildingContext $metadataBuildingContext
     */
    public function __construct(
        \ReflectionClass $reflectionClass,
        array $classAnnotations,
        Mapping\ClassMetadata $classMetadata,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    )
    {
        $this->reflectionClass         = $reflectionClass;
        $this->classAnnotations        = $classAnnotations;
        $this->classMetadata           = $classMetadata;
        $this->metadataBuildingContext = $metadataBuildingContext;
    }

    /**
     * @return Mapping\ClassMetadata
     */
    public function bind() : Mapping\ClassMetadata
    {
        $classMetadata = $this->classMetadata;

        $this->processEntityAnnotation($classMetadata, $this->classAnnotations[Annotation\Entity::class]);

        return $classMetadata;
    }

    /**
     * @param Mapping\ClassMetadata $classMetadata
     * @param Annotation\Entity     $entityAnnotation
     *
     * @return void
     */
    private function processEntityAnnotation(
        Mapping\ClassMetadata $classMetadata,
        Annotation\Entity $entityAnnotation
    ) : void
    {
        if ($entityAnnotation->repositoryClass !== null) {
            $repositoryClassName = $classMetadata->fullyQualifiedClassName($entityAnnotation->repositoryClass);

            $classMetadata->setCustomRepositoryClassName($repositoryClassName);
        }

        if ($entityAnnotation->readOnly) {
            $classMetadata->asReadOnly();
        }

        $classMetadata->isMappedSuperclass = false;
        $classMetadata->isEmbeddedClass = false;
    }
}
