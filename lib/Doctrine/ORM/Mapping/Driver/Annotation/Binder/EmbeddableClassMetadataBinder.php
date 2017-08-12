<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver\Annotation\Binder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;

/**
 * Class EmbeddableClassMetadataBinder
 *
 * @package Doctrine\ORM\Mapping\Driver\Annotation\Binder
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class EmbeddableClassMetadataBinder
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

        $this->processEmbeddableAnnotation($classMetadata, $this->classMetadata[Annotation\Embeddable::class]);

        return $classMetadata;
    }

    /**
     * @param Mapping\ClassMetadata $classMetadata
     * @param Annotation\Embeddable $embeddableAnnotation
     *
     * @return void
     */
    private function processEmbeddableAnnotation(
        Mapping\ClassMetadata $classMetadata,
        Annotation\Embeddable $embeddableAnnotation
    ) : void
    {
        $classMetadata->isMappedSuperclass = false;
        $classMetadata->isEmbeddedClass = true;
    }
}
