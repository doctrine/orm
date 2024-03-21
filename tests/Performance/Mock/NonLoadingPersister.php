<?php

declare(strict_types=1);

namespace Doctrine\Performance\Mock;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;

/**
 * A persister that doesn't actually load given objects
 */
class NonLoadingPersister extends BasicEntityPersister
{
    public function __construct(
        ClassMetadata $class,
    ) {
        $this->class = $class;
    }

    public function loadById(array $identifier, object|null $entity = null): object|null
    {
        return $entity ?? new ($this->class->name)();
    }
}
