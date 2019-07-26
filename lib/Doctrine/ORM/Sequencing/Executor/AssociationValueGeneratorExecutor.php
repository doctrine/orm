<?php

declare(strict_types=1);

namespace Doctrine\ORM\Sequencing\Executor;

use Doctrine\ORM\EntityManagerInterface;

final class AssociationValueGeneratorExecutor implements ValueGenerationExecutor
{
    /**
     * {@inheritdoc}
     */
    public function execute(EntityManagerInterface $entityManager, object $entity) : array
    {
        // value set by inverse side
        return [];
    }

    public function isDeferred() : bool
    {
        return true;
    }
}
