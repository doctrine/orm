<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function assert;

class ClassMetadataBuilder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /** @var CacheMetadataBuilder */
    protected $cacheMetadataBuilder;

    /** @var string */
    private $className;

    /** @var Mapping\ComponentMetadata */
    private $parentMetadata;

    /** @var Annotation\Cache|null */
    protected $cacheAnnotation;

    public function __construct(
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext,
        ?CacheMetadataBuilder $cacheMetadataBuilder = null
    ) {
        $this->metadataBuildingContext = $metadataBuildingContext;
        $this->cacheMetadataBuilder    = $cacheMetadataBuilder ?: new CacheMetadataBuilder($metadataBuildingContext);
    }

    public function getClassName(string $className) : ClassMetadataBuilder
    {
        $this->className = $className;

        return $this;
    }

    public function withParentMetadata(Mapping\ComponentMetadata $parentMetadata) : ClassMetadataBuilder
    {
        $this->parentMetadata = $parentMetadata;

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

    public function build() : Mapping\ClassMetadata
    {
        assert($this->className !== null);

        $reflectionService = $this->metadataBuildingContext->getReflectionService();
        $reflectionClass   = $reflectionService->getClass($this->className);
        $className         = $reflectionClass ? $reflectionClass->getName() : $this->className;

        $classMetadata = new Mapping\ClassMetadata($className, $this->parentMetadata);

        $this->buildCache($classMetadata);

        return $classMetadata;
    }

    protected function buildCache(Mapping\ClassMetadata $classMetadata) : void
    {
        if ($this->cacheAnnotation !== null) {
            $classMetadata->setCache($this->cacheMetadataBuilder->build());
        }
    }
}
