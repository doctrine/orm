<?php

declare(strict_types=1);

namespace Doctrine\ORM\Sequencing\Planning;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;

class CompositeValueGenerationPlan implements ValueGenerationPlan
{
    /** @var ClassMetadata */
    private $class;

    /** @var ValueGenerationExecutor[] */
    private $executors;

    /**
     * @param ValueGenerationExecutor[] $executors
     */
    public function __construct(ClassMetadata $metadata, array $executors)
    {
        $this->class     = $metadata;
        $this->executors = $executors;
    }

    public function executeImmediate(EntityManagerInterface $entityManager, object $entity) : void
    {
        foreach ($this->executors as $executor) {
            if ($executor->isDeferred()) {
                continue;
            }

            $this->dispatchExecutor($executor, $entity, $entityManager);
        }
    }

    public function executeDeferred(EntityManagerInterface $entityManager, object $entity) : void
    {
        foreach ($this->executors as $executor) {
            if (! $executor->isDeferred()) {
                continue;
            }

            $this->dispatchExecutor($executor, $entity, $entityManager);
        }
    }

    private function dispatchExecutor(
        ValueGenerationExecutor $executor,
        object $entity,
        EntityManagerInterface $entityManager
    ) : void {
        foreach ($executor->execute($entityManager, $entity) as $columnName => $value) {
            // TODO LocalColumnMetadata are currently shadowed and only exposed as FieldMetadata
            /** @var FieldMetadata $column */
            $column = $this->class->getColumn($columnName);
            $column->setValue($entity, $value);
        }
    }

    public function containsDeferred() : bool
    {
        foreach ($this->executors as $executor) {
            if ($executor->isDeferred()) {
                return true;
            }
        }

        return false;
    }
}
