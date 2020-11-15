<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function assert;
use function class_exists;
use function interface_exists;

class EmbeddedMetadataBuilder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    protected $metadataBuildingContext;

    /** @var ValueGeneratorMetadataBuilder */
    private $valueGeneratorMetadataBuilder;

    /** @var Mapping\ClassMetadata */
    protected $componentMetadata;

    /** @var string */
    protected $fieldName;

    /** @var Annotation\Embedded */
    private $embeddedAnnotation;

    /** @var Annotation\Id|null */
    private $idAnnotation;

    public function __construct(
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext,
        ?ValueGeneratorMetadataBuilder $valueGeneratorMetadataBuilder = null
    ) {
        $this->metadataBuildingContext       = $metadataBuildingContext;
        $this->valueGeneratorMetadataBuilder = $valueGeneratorMetadataBuilder ?: new ValueGeneratorMetadataBuilder($metadataBuildingContext);
    }

    public function withComponentMetadata(Mapping\ClassMetadata $componentMetadata) : EmbeddedMetadataBuilder
    {
        $this->componentMetadata = $componentMetadata;

        return $this;
    }

    public function withFieldName(string $fieldName) : EmbeddedMetadataBuilder
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    public function withEmbeddedAnnotation(Annotation\Embedded $embeddedAnnotation) : EmbeddedMetadataBuilder
    {
        $this->embeddedAnnotation = $embeddedAnnotation;

        return $this;
    }

    public function withIdAnnotation(?Annotation\Id $idAnnotation) : EmbeddedMetadataBuilder
    {
        $this->idAnnotation = $idAnnotation;

        return $this;
    }

    public function build() : Mapping\EmbeddedMetadata
    {
        // Validate required fields
        assert($this->componentMetadata !== null);
        assert($this->embeddedAnnotation !== null);
        assert($this->fieldName !== null);

        $componentClassName = $this->componentMetadata->getClassName();
        $embeddedMetadata   = new Mapping\EmbeddedMetadata($this->fieldName);

        $embeddedMetadata->setSourceEntity($componentClassName);
        $embeddedMetadata->setTargetEntity($this->getTargetEmbedded($this->embeddedAnnotation->class));

        if (! empty($this->embeddedAnnotation->columnPrefix)) {
            $embeddedMetadata->setColumnPrefix($this->embeddedAnnotation->columnPrefix);
        }

        if ($this->idAnnotation !== null) {
            $embeddedMetadata->setPrimaryKey(true);
        }

        return $embeddedMetadata;
    }

    /**
     * Attempts to resolve target embedded.
     *
     * @param string $targetEmbedded The proposed target embedded
     *
     * @return string The processed target embedded
     *
     * @throws Mapping\MappingException If a target embedded is not valid.
     */
    protected function getTargetEmbedded(string $targetEmbedded) : string
    {
        // Validate if target embedded is defined
        if (! $targetEmbedded) {
            throw Mapping\MappingException::missingEmbeddedClass($this->fieldName);
        }

        // Validate that target entity exists
        if (! (class_exists($targetEmbedded) || interface_exists($targetEmbedded))) {
            throw Mapping\MappingException::invalidTargetEmbeddedClass(
                $targetEmbedded,
                $this->componentMetadata->getClassName(),
                $this->fieldName
            );
        }

        return $targetEmbedded;
    }
}
