<?php

declare(strict_types=1);

namespace Doctrine\ORM\Sequencing\Planning;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;

class SingleValueGenerationPlan implements ValueGenerationPlan
{
    /** @var ClassMetadata */
    private $class;

    /** @var ValueGenerationExecutor */
    private $executor;

    public function __construct(ClassMetadata $class, ValueGenerationExecutor $executor)
    {
        $this->class    = $class;
        $this->executor = $executor;
    }

    public function executeImmediate(EntityManagerInterface $entityManager, object $entity) : void
    {
        if (! $this->executor->isDeferred()) {
            $this->dispatchExecutor($entity, $entityManager);
        }
    }

    public function executeDeferred(EntityManagerInterface $entityManager, object $entity) : void
    {
        if ($this->executor->isDeferred()) {
            $this->dispatchExecutor($entity, $entityManager);
        }
    }

    private function dispatchExecutor(object $entity, EntityManagerInterface $entityManager) : void
    {
        foreach ($this->executor->execute($entityManager, $entity) as $columnName => $value) {
            // TODO LocalColumnMetadata are currently shadowed and only exposed as FieldMetadata
            /** @var FieldMetadata $column */
            $column = $this->class->getColumn($columnName);
            $column->setValue($entity, $value);
        }
    }

    public function containsDeferred() : bool
    {
        return $this->executor->isDeferred();
    }
}
