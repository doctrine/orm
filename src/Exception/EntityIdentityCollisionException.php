<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use function get_class;
use function sprintf;

final class EntityIdentityCollisionException extends ORMException
{
    /**
     * @param object $existingEntity
     * @param object $newEntity
     */
    public static function create($existingEntity, $newEntity, string $idHash): self
    {
        return new self(
            sprintf(
                <<<'EXCEPTION'
While adding an entity of class %s with an ID hash of "%s" to the identity map,
another object of class %s was already present for the same ID. This exception
is a safeguard against an internal inconsistency - IDs should uniquely map to
entity object instances. This problem may occur if:

- you use application-provided IDs and reuse ID values;
- database-provided IDs are reassigned after truncating the database without
clearing the EntityManager;
- you might have been using EntityManager#getReference() to create a reference
for a nonexistent ID that was subsequently (by the RDBMS) assigned to another
entity.

Otherwise, it might be an ORM-internal inconsistency, please report it.
EXCEPTION
                ,
                get_class($newEntity),
                $idHash,
                get_class($existingEntity)
            )
        );
    }
}
