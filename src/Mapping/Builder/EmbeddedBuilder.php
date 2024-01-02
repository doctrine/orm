<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

/**
 * Embedded Builder
 *
 * @link        www.doctrine-project.com
 */
class EmbeddedBuilder
{
    /** @param mixed[] $mapping */
    public function __construct(
        private readonly ClassMetadataBuilder $builder,
        private array $mapping,
    ) {
    }

    /**
     * Sets the column prefix for all of the embedded columns.
     *
     * @return $this
     */
    public function setColumnPrefix(string $columnPrefix): static
    {
        $this->mapping['columnPrefix'] = $columnPrefix;

        return $this;
    }

    /**
     * Finalizes this embeddable and attach it to the ClassMetadata.
     *
     * Without this call an EmbeddedBuilder has no effect on the ClassMetadata.
     */
    public function build(): ClassMetadataBuilder
    {
        $cm = $this->builder->getClassMetadata();

        $cm->mapEmbedded($this->mapping);

        return $this->builder;
    }
}
