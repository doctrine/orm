<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use function assert;

class DiscriminatorColumnMetadataBuilder
{
    /** @var Mapping\ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /** @var Mapping\ClassMetadata */
    private $componentMetadata;

    /** @var Annotation\DiscriminatorColumn|null */
    private $discriminatorColumnAnnotation;

    public function __construct(Mapping\ClassMetadataBuildingContext $metadataBuildingContext)
    {
        $this->metadataBuildingContext = $metadataBuildingContext;
    }

    public function withComponentMetadata(Mapping\ClassMetadata $componentMetadata) : DiscriminatorColumnMetadataBuilder
    {
        $this->componentMetadata = $componentMetadata;

        return $this;
    }

    public function withDiscriminatorColumnAnnotation(
        ?Annotation\DiscriminatorColumn $discriminatorColumn
    ) : DiscriminatorColumnMetadataBuilder {
        $this->discriminatorColumnAnnotation = $discriminatorColumn;

        return $this;
    }

    /**
     * @throws DBALException
     */
    public function build() : Mapping\DiscriminatorColumnMetadata
    {
        // Validate required fields
        \assert($this->componentMetadata !== null);

        $discriminatorColumn = new Mapping\DiscriminatorColumnMetadata();

        $discriminatorColumn->setTableName($this->componentMetadata->getTableName());
        $discriminatorColumn->setColumnName('dtype');
        $discriminatorColumn->setType(Type::getType('string'));
        $discriminatorColumn->setLength(255);

        if ($this->discriminatorColumnAnnotation === null) {
            return $discriminatorColumn;
        }

        $annotation = $this->discriminatorColumnAnnotation;

        $discriminatorColumn->setType(Type::getType($annotation->type ?? 'string'));
        $discriminatorColumn->setColumnName($annotation->name);

        if (! empty($annotation->columnDefinition)) {
            $discriminatorColumn->setColumnDefinition($annotation->columnDefinition);
        }

        if (! empty($annotation->length)) {
            $discriminatorColumn->setLength($annotation->length);
        }

        return $discriminatorColumn;
    }
}
