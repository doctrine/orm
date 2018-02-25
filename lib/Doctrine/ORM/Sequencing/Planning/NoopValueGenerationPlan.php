<?php

declare(strict_types=1);

namespace Doctrine\ORM\Sequencing\Planning;

use Doctrine\ORM\EntityManagerInterface;

class NoopValueGenerationPlan implements ValueGenerationPlan
{
    public function executeImmediate(EntityManagerInterface $entityManager, object $entity) : void
    {
        // no-op
    }

    public function executeDeferred(EntityManagerInterface $entityManager, object $entity) : void
    {
        // no-op
    }

    public function containsDeferred() : bool
    {
        return false;
    }
}
