<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function assert;
use function constant;
use function sprintf;

class ClassMetadataBuilder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /** @var TableMetadataBuilder */
    protected $tableMetadataBuilder;

    /** @var CacheMetadataBuilder */
    protected $cacheMetadataBuilder;

    /** @var DiscriminatorColumnMetadataBuilder */
    protected $discriminatorColumnMetadataBuilder;

    /** @var string */
    private $className;

    /** @var Mapping\ComponentMetadata|null */
    private $parentMetadata;

    /** @var Annotation\Entity|null */
    protected $entityAnnotation;

    /** @var Annotation\Embeddable|null */
    protected $embeddableAnnotation;

    /** @var Annotation\MappedSuperclass|null */
    protected $mappedSuperclassAnnotation;

    /** @var Annotation\InheritanceType|null */
    protected $inheritanceTypeAnnotation;

    /** @var Annotation\Table|null */
    protected $tableAnnotation;

    /** @var Annotation\Cache|null */
    protected $cacheAnnotation;

    /** @var Annotation\ChangeTrackingPolicy|null */
    protected $changeTrackingPolicyAnnotation;

    /** @var Annotation\DiscriminatorColumn|null */
    protected $discriminatorColumnAnnotation;

    /** @var Annotation\DiscriminatorMap|null */
    protected $discriminatorMapAnnotation;

    public function __construct(
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext,
        ?TableMetadataBuilder $tableMetadataBuilder = null,
        ?CacheMetadataBuilder $cacheMetadataBuilder = null,
        ?DiscriminatorColumnMetadataBuilder $discriminatorColumnMetadataBuilder = null
    ) {
        $this->metadataBuildingContext            = $metadataBuildingContext;
        $this->tableMetadataBuilder               = $tableMetadataBuilder ?: new TableMetadataBuilder($metadataBuildingContext);
        $this->cacheMetadataBuilder               = $cacheMetadataBuilder ?: new CacheMetadataBuilder($metadataBuildingContext);
        $this->discriminatorColumnMetadataBuilder = $discriminatorColumnMetadataBuilder?: new DiscriminatorColumnMetadataBuilder($metadataBuildingContext);
    }

    public function withClassName(string $className) : ClassMetadataBuilder
    {
        $this->className = $className;

        return $this;
    }

    public function withParentMetadata(?Mapping\ComponentMetadata $parentMetadata) : ClassMetadataBuilder
    {
        $this->parentMetadata = $parentMetadata;

        return $this;
    }

    public function withEntityAnnotation(?Annotation\Entity $entityAnnotation) : ClassMetadataBuilder
    {
        $this->entityAnnotation = $entityAnnotation;

        return $this;
    }

    public function withEmbeddableAnnotation(?Annotation\Embeddable $embeddableAnnotation) : ClassMetadataBuilder
    {
        $this->embeddableAnnotation = $embeddableAnnotation;

        return $this;
    }

    public function withMappedSuperclassAnnotation(?Annotation\MappedSuperclass $mappedSuperclassAnnotation) : ClassMetadataBuilder
    {
        $this->mappedSuperclassAnnotation = $mappedSuperclassAnnotation;

        return $this;
    }

    public function withInheritanceTypeAnnotation(?Annotation\InheritanceType $inheritanceTypeAnnotation) : ClassMetadataBuilder
    {
        $this->inheritanceTypeAnnotation = $inheritanceTypeAnnotation;

        return $this;
    }

    public function withTableAnnotation(?Annotation\Table $tableAnnotation) : ClassMetadataBuilder
    {
        $this->tableAnnotation = $tableAnnotation;

        if ($tableAnnotation !== null) {
            $this->tableMetadataBuilder->withTableAnnotation($tableAnnotation);
        }

        return $this;
    }

    public function withCacheAnnotation(?Annotation\Cache $cacheAnnotation) : ClassMetadataBuilder
    {
        $this->cacheAnnotation = $cacheAnnotation;

        if ($cacheAnnotation !== null) {
            $this->cacheMetadataBuilder->withCacheAnnotation($cacheAnnotation);
        }

        return $this;
    }

    public function withChangeTrackingPolicyAnnotation(?Annotation\ChangeTrackingPolicy $changeTrackingPolicyAnnotation) : ClassMetadataBuilder
    {
        $this->changeTrackingPolicyAnnotation = $changeTrackingPolicyAnnotation;

        return $this;
    }

    public function withDiscriminatorColumnAnnotation(?Annotation\DiscriminatorColumn $discriminatorColumnAnnotation) : ClassMetadataBuilder
    {
        $this->discriminatorColumnAnnotation = $discriminatorColumnAnnotation;

        if ($discriminatorColumnAnnotation !== null) {
            $this->discriminatorColumnMetadataBuilder->withDiscriminatorColumnAnnotation($discriminatorColumnAnnotation);
        }

        return $this;
    }

    public function withDiscriminatorMapAnnotation(?Annotation\DiscriminatorMap $discriminatorMapAnnotation) : ClassMetadataBuilder
    {
        $this->discriminatorMapAnnotation = $discriminatorMapAnnotation;

        return $this;
    }

    public function build() : Mapping\ClassMetadata
    {
        \assert($this->className !== null);

        $reflectionService = $this->metadataBuildingContext->getReflectionService();
        $reflectionClass   = $reflectionService->getClass($this->className);
        $className         = $reflectionClass ? $reflectionClass->getName() : $this->className;

        $classMetadata = new Mapping\ClassMetadata($className, $this->parentMetadata);

        switch (true) {
            case $this->entityAnnotation !== null:
                $this->buildEntityClassMetadata($classMetadata);
                break;

            case $this->mappedSuperclassAnnotation !== null:
                $this->buildMappedSuperclassMetadata($classMetadata);
                break;

            case $this->embeddableAnnotation !== null:
                $this->buildEmbeddableMetadata($classMetadata);
                break;

            default:
                throw Mapping\MappingException::classIsNotAValidEntityOrMappedSuperClass($this->className);
        }

        $this->buildCache($classMetadata);

        return $classMetadata;
    }

    protected function buildEntityClassMetadata(Mapping\ClassMetadata $classMetadata) : void
    {
        $classMetadata->isMappedSuperclass = false;
        $classMetadata->isEmbeddedClass    = false;

        if ($this->entityAnnotation->repositoryClass !== null) {
            $classMetadata->setCustomRepositoryClassName($this->entityAnnotation->repositoryClass);
        }

        if ($this->entityAnnotation->readOnly) {
            $classMetadata->asReadOnly();
        }

        $this->buildTable($classMetadata);
        $this->buildInheritance($classMetadata);
        $this->buildChangeTrackingPolicy($classMetadata);
    }

    protected function buildMappedSuperclassMetadata(Mapping\ClassMetadata $classMetadata) : void
    {
        $classMetadata->isMappedSuperclass = true;
        $classMetadata->isEmbeddedClass    = false;

        if ($this->mappedSuperclassAnnotation->repositoryClass !== null) {
            $classMetadata->setCustomRepositoryClassName($this->mappedSuperclassAnnotation->repositoryClass);
        }
    }

    protected function buildEmbeddableMetadata(Mapping\ClassMetadata $classMetadata) : void
    {
        $classMetadata->isMappedSuperclass = false;
        $classMetadata->isEmbeddedClass    = true;
    }

    protected function buildTable(Mapping\ClassMetadata $classMetadata) : void
    {
        $parentMetadata = $classMetadata->getParent();
        $tableMetadata  = null;

        if ($parentMetadata instanceof Mapping\ClassMetadata
            && $parentMetadata->inheritanceType === Mapping\InheritanceType::SINGLE_TABLE) {
            // Handle the case where a middle mapped super class inherits from a single table inheritance tree.
            do {
                if (! $parentMetadata->isMappedSuperclass) {
                    $tableMetadata = $parentMetadata->table;
                    break;
                }

                $parentMetadata = $parentMetadata->getParent();
            } while ($parentMetadata !== null);
        } else {
            $tableMetadata = $this->tableMetadataBuilder
                ->withEntityClassMetadata($classMetadata)
                ->withTableAnnotation($this->tableAnnotation)
                ->build();
        }

        $classMetadata->setTable($tableMetadata);
    }

    protected function buildInheritance(Mapping\ClassMetadata $classMetadata) : void
    {
        if ($this->inheritanceTypeAnnotation !== null) {
            $typeName = $this->inheritanceTypeAnnotation->value;
            $type     = \constant(\sprintf('%s::%s', Mapping\InheritanceType::class, $typeName));

            $classMetadata->setInheritanceType($type);

            if ($type !== Mapping\InheritanceType::NONE) {
                $discriminatorColumn = $this->discriminatorColumnMetadataBuilder
                    ->withComponentMetadata($classMetadata)
                    ->withDiscriminatorColumnAnnotation($this->discriminatorColumnAnnotation)
                    ->build();

                $classMetadata->setDiscriminatorColumn($discriminatorColumn);

                if ($this->discriminatorMapAnnotation !== null) {
                    $classMetadata->setDiscriminatorMap($this->discriminatorMapAnnotation->value);
                }
            }
        }
    }

    protected function buildCache(Mapping\ClassMetadata $classMetadata) : void
    {
        if ($this->cacheAnnotation !== null) {
            $cacheMetadata = $this->cacheMetadataBuilder
                ->withComponentMetadata($classMetadata)
                ->build();

            $classMetadata->setCache($cacheMetadata);
        }
    }

    protected function buildChangeTrackingPolicy(Mapping\ClassMetadata $classMetadata) : void
    {
        if ($this->changeTrackingPolicyAnnotation !== null) {
            $policyName = $this->changeTrackingPolicyAnnotation->value;
            $policy     = \constant(\sprintf('%s::%s', Mapping\ChangeTrackingPolicy::class, $policyName));

            $classMetadata->setChangeTrackingPolicy($policy);
        }
    }
}
