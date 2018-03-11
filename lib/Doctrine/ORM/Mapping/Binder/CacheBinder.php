<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Binder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function constant;
use function sprintf;
use function str_replace;
use function strtolower;
use function strtoupper;

class CacheBinder
{
    /** @var Mapping\ComponentMetadata */
    private $metadata;

    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    public function __construct(
        Mapping\ComponentMetadata $metadata,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    ) {
        $this->metadata                = $metadata;
        $this->metadataBuildingContext = $metadataBuildingContext;
    }

    public function bind(Annotation\Cache $cacheAnnotation, ?string $fieldName = null) : Mapping\CacheMetadata
    {
        $baseRegion    = strtolower(str_replace('\\', '_', $this->metadata->getRootClassName()));
        $defaultRegion = $baseRegion . ($fieldName ? '__' . $fieldName : '');

        $usage  = constant(sprintf('%s::%s', Mapping\CacheUsage::class, strtoupper($cacheAnnotation->usage)));
        $region = $cacheAnnotation->region ?: $defaultRegion;

        return new Mapping\CacheMetadata($usage, $region);
    }
}