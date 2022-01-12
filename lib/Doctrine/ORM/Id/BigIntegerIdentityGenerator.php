<?php

declare(strict_types=1);

namespace Doctrine\ORM\Id;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Id generator that obtains IDs from special "identity" columns. These are columns
 * that automatically get a database-generated, auto-incremented identifier on INSERT.
 * This generator obtains the last insert id after such an insert.
 */
class BigIntegerIdentityGenerator extends AbstractIdGenerator
{
    /**
     * @param string|null $sequenceName The name of the sequence to pass to lastInsertId()
     *                                  to obtain the last generated identifier within the current
     *                                  database session/connection, if any.
     */
    public function __construct(
        private ?string $sequenceName = null
    ) {
    }

    public function generateId(EntityManagerInterface $em, ?object $entity): string
    {
        return (string) $em->getConnection()->lastInsertId($this->sequenceName);
    }

    public function isPostInsertGenerator(): bool
    {
        return true;
    }
}
