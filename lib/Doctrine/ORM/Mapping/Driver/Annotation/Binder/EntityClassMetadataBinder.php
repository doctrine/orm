<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver\Annotation\Binder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;

class EntityClassMetadataBinder
{
    /**
     * [dreaming] One day we would eliminate this and only do: $reflectionClass->getAnnotations()
     *
     * @var Annotation\Annotation[]
     */
    private $classAnnotations;

    /**
     * @todo guilhermeblanco This should disappear once we instantiation happens in the Driver
     *
     * @var Mapping\ClassMetadata
     */
    private $classMetadata;

    /**
     * @param Annotation\Annotation[] $classAnnotations
     */
    public function __construct(
        array $classAnnotations,
        Mapping\ClassMetadata $classMetadata
    ) {
        $this->classAnnotations = $classAnnotations;
        $this->classMetadata    = $classMetadata;
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
