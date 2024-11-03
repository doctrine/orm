<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

/**
 * OneToMany Association Builder
 *
 * @link        www.doctrine-project.com
 */
class OneToManyAssociationBuilder extends AssociationBuilder
{
    /**
     * @phpstan-param array<string, string> $fieldNames
     *
     * @return $this
     */
    public function setOrderBy(array $fieldNames): static
    {
        $this->mapping['orderBy'] = $fieldNames;

        return $this;
    }

    /** @return $this */
    public function setIndexBy(string $fieldName): static
    {
        $this->mapping['indexBy'] = $fieldName;

        return $this;
    }

    public function build(): ClassMetadataBuilder
    {
        $mapping = $this->mapping;
        if ($this->joinColumns) {
            $mapping['joinColumns'] = $this->joinColumns;
        }

        $cm = $this->builder->getClassMetadata();
        $cm->mapOneToMany($mapping);

        return $this->builder;
    }
}
