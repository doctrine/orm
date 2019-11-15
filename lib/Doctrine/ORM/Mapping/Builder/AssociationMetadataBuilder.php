<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function array_diff;
use function array_intersect;
use function array_map;
use function class_exists;
use function constant;
use function count;
use function defined;
use function in_array;
use function interface_exists;
use function sprintf;

abstract class AssociationMetadataBuilder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    protected $metadataBuildingContext;

    /** @var CacheMetadataBuilder */
    protected $cacheMetadataBuilder;

    /** @var Mapping\ClassMetadata */
    protected $componentMetadata;

    /** @var string */
    protected $fieldName;

    /** @var Annotation\Cache|null */
    protected $cacheAnnotation;

    public function __construct(
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext,
        ?CacheMetadataBuilder $cacheMetadataBuilder = null
    ) {
        $this->metadataBuildingContext = $metadataBuildingContext;
        $this->cacheMetadataBuilder    = $cacheMetadataBuilder ?: new CacheMetadataBuilder($metadataBuildingContext);
    }

    public function withComponentMetadata(Mapping\ClassMetadata $componentMetadata) : AssociationMetadataBuilder
    {
        $this->componentMetadata = $componentMetadata;

        $this->cacheMetadataBuilder->withComponentMetadata($componentMetadata);

        return $this;
    }

    public function withFieldName(string $fieldName) : AssociationMetadataBuilder
    {
        $this->fieldName = $fieldName;

        $this->cacheMetadataBuilder->withFieldName($fieldName);

        return $this;
    }

    public function withCacheAnnotation(?Annotation\Cache $cacheAnnotation) : AssociationMetadataBuilder
    {
        $this->cacheAnnotation = $cacheAnnotation;

        if ($cacheAnnotation !== null) {
            $this->cacheMetadataBuilder->withCacheAnnotation($cacheAnnotation);
        }

        return $this;
    }

    protected function buildCache(Mapping\AssociationMetadata $associationMetadata) : void
    {
        if ($this->cacheAnnotation !== null) {
            $associationMetadata->setCache($this->cacheMetadataBuilder->build());
        }
    }

    /**
     * Attempts to resolve target entity.
     *
     * @param string $targetEntity The proposed target entity
     *
     * @return string The processed target entity
     *
     * @throws Mapping\MappingException If a target entity is not valid.
     */
    protected function getTargetEntity(string $targetEntity) : string
    {
        // Validate if target entity is defined
        if (! $targetEntity) {
            throw Mapping\MappingException::missingTargetEntity($this->fieldName);
        }

        // Validate that target entity exists
        if (! (\class_exists($targetEntity) || \interface_exists($targetEntity))) {
            throw Mapping\MappingException::invalidTargetEntityClass(
                $targetEntity,
                $this->componentMetadata->getClassName(),
                $this->fieldName
            );
        }

        return $targetEntity;
    }

    /**
     * Attempts to resolve the cascade modes.
     *
     * @param string[] $originalCascades The original unprocessed field cascades.
     *
     * @return string[] The processed field cascades.
     *
     * @throws Mapping\MappingException If a cascade option is not valid.
     */
    protected function getCascade(array $originalCascades) : array
    {
        $cascadeTypes = ['remove', 'persist', 'refresh'];
        $cascades     = \array_map('strtolower', $originalCascades);

        if (\in_array('all', $cascades, true)) {
            $cascades = $cascadeTypes;
        }

        if (\count($cascades) !== \count(\array_intersect($cascades, $cascadeTypes))) {
            $diffCascades = \array_diff($cascades, \array_intersect($cascades, $cascadeTypes));

            throw Mapping\MappingException::invalidCascadeOption(
                $diffCascades,
                $this->componentMetadata->getClassName(),
                $this->fieldName
            );
        }

        return $cascades;
    }

    /**
     * Attempts to resolve the fetch mode.
     *
     * @param string $fetchMode The fetch mode.
     *
     * @return string The fetch mode as defined in ClassMetadata.
     *
     * @throws Mapping\MappingException If the fetch mode is not valid.
     */
    protected function getFetchMode($fetchMode) : string
    {
        $fetchModeConstant = \sprintf('%s::%s', Mapping\FetchMode::class, $fetchMode);

        if (! \defined($fetchModeConstant)) {
            throw Mapping\MappingException::invalidFetchMode($this->componentMetadata->getClassName(), $fetchMode);
        }

        return \constant($fetchModeConstant);
    }
}
