<?php

declare(strict_types=1);

namespace Doctrine\ORM\Sequencing\Planning;

use Doctrine\ORM\EntityManagerInterface;

interface ValueGenerationPlan
{
    public function executeImmediate(EntityManagerInterface $entityManager, object $entity) : void;

    public function executeDeferred(EntityManagerInterface $entityManager, object $entity) : void;

    public function containsDeferred() : bool;
}
