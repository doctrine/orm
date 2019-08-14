<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Annotation;
use Doctrine\ORM\Mapping;
use RuntimeException;
use function array_merge;
use function array_unique;
use function assert;
use function count;
use function reset;
use function sprintf;

class OneToOneAssociationMetadataBuilder extends ToOneAssociationMetadataBuilder
{
    /** @var Annotation\OneToOne */
    private $oneToOneAnnotation;

    public function withOneToOneAnnotation(Annotation\OneToOne $oneToOneAnnotation) : OneToOneAssociationMetadataBuilder
    {
        $this->oneToOneAnnotation = $oneToOneAnnotation;

        return $this;
    }

    /**
     * @internal Association metadata order of definition settings is important.
     */
    public function build() : Mapping\OneToOneAssociationMetadata
    {
        // Validate required fields
        assert($this->componentMetadata !== null);
        assert($this->oneToOneAnnotation !== null);
        assert($this->fieldName !== null);

        $componentClassName  = $this->componentMetadata->getClassName();
        $associationMetadata = new Mapping\OneToOneAssociationMetadata($this->fieldName);

        $associationMetadata->setSourceEntity($componentClassName);
        $associationMetadata->setTargetEntity($this->getTargetEntity($this->oneToOneAnnotation->targetEntity));
        $associationMetadata->setCascade($this->getCascade($this->oneToOneAnnotation->cascade));
        $associationMetadata->setFetchMode($this->getFetchMode($this->oneToOneAnnotation->fetch));

        if ($this->oneToOneAnnotation->orphanRemoval) {
            $associationMetadata->setOrphanRemoval($this->oneToOneAnnotation->orphanRemoval);

            // Orphan removal also implies a cascade remove
            $associationMetadata->setCascade(array_unique(array_merge($associationMetadata->getCascade(), ['remove'])));
        }

        if (! empty($this->oneToOneAnnotation->mappedBy)) {
            $associationMetadata->setMappedBy($this->oneToOneAnnotation->mappedBy);
            $associationMetadata->setOwningSide(false);
        }

        if (! empty($this->oneToOneAnnotation->inversedBy)) {
            $associationMetadata->setInversedBy($this->oneToOneAnnotation->inversedBy);
            $associationMetadata->setOwningSide(true);
        }

        $this->buildCache($associationMetadata);
        $this->buildPrimaryKey($associationMetadata);

        // Check for owning side to consider join column
        if (! $associationMetadata->isOwningSide()) {
            return $associationMetadata;
        }

        $this->buildJoinColumns($associationMetadata);

        // @todo guilhermeblanco The below block of code modifies component metadata properties, and it should be moved
        //                       to the component metadata builder instead of here.

        // Set unique constraint for owning side in all columns
        if ($associationMetadata->isOwningSide()) {
            $this->buildUniqueConstraints($associationMetadata);
        }

        return $associationMetadata;
    }

    private function buildUniqueConstraints(Mapping\OneToOneAssociationMetadata $associationMetadata) : void
    {
        $joinColumns = $associationMetadata->getJoinColumns();

        if (count($joinColumns) === 1) {
            $joinColumn = reset($joinColumns);

            if (! $associationMetadata->isPrimaryKey()) {
                $joinColumn->setUnique(true);
            }

            return;
        }

        $tableMetadata = $this->componentMetadata->table;

        if (! $tableMetadata) {
            $exception = 'ClassMetadata::setTable() has to be called before defining a one to one relationship.';

            throw new RuntimeException($exception);
        }

        $uniqueConstraintColumns = [];

        foreach ($joinColumns as $joinColumnMetadata) {
            if ($this->componentMetadata->inheritanceType !== Mapping\InheritanceType::SINGLE_TABLE) {
                $uniqueConstraintColumns[] = $joinColumnMetadata->getColumnName();
            }
        }

        if ($uniqueConstraintColumns) {
            $tableMetadata->addUniqueConstraint([
                'name'    => sprintf('%s_uniq', $this->fieldName),
                'columns' => $uniqueConstraintColumns,
                'options' => [],
                'flags'   => [],
            ]);
        }
    }
}
