<?php

declare(strict_types=1);

namespace Doctrine\ORM\Sequencing\Planning;

use Doctrine\ORM\EntityManagerInterface;

interface ValueGenerationExecutor
{
    /**
     * @return mixed[]
     */
    public function execute(EntityManagerInterface $entityManager, object $entity) : array;

    public function isDeferred() : bool;
}
