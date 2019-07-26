<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function assert;
use function constant;
use function sprintf;
use function str_replace;
use function strtolower;
use function strtoupper;

class CacheMetadataBuilder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /** @var Mapping\ClassMetadata */
    private $componentMetadata;

    /** @var string|null */
    private $fieldName;

    /** @var Annotation\Cache */
    private $cacheAnnotation;

    public function __construct(Mapping\ClassMetadataBuildingContext $metadataBuildingContext)
    {
        $this->metadataBuildingContext = $metadataBuildingContext;
    }

    public function withComponentMetadata(Mapping\ClassMetadata $componentMetadata) : CacheMetadataBuilder
    {
        $this->componentMetadata = $componentMetadata;

        return $this;
    }

    public function withFieldName(?string $fieldName) : CacheMetadataBuilder
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    public function withCacheAnnotation(Annotation\Cache $cacheAnnotation) : CacheMetadataBuilder
    {
        $this->cacheAnnotation = $cacheAnnotation;

        return $this;
    }

    public function build() : Mapping\CacheMetadata
    {
        // Validate required fields
        assert($this->componentMetadata !== null);
        assert($this->cacheAnnotation !== null);

        $componentName = $this->componentMetadata->getRootClassName();
        $baseRegion    = strtolower(str_replace('\\', '_', $componentName));
        $defaultRegion = $baseRegion . ($this->fieldName ? '__' . $this->fieldName : '');

        $usage  = constant(sprintf('%s::%s', Mapping\CacheUsage::class, strtoupper($this->cacheAnnotation->usage)));
        $region = $this->cacheAnnotation->region ?: $defaultRegion;

        return new Mapping\CacheMetadata($usage, $region);
    }
}
