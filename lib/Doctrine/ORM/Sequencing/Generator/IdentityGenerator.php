<?php

declare(strict_types=1);

namespace Doctrine\ORM\Sequencing\Generator;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Id generator that obtains IDs from special "identity" columns. These are columns
 * that automatically get a database-generated, auto-incremented identifier on INSERT.
 * This generator obtains the last insert id after such an insert.
 */
class IdentityGenerator implements Generator
{
    /**
     * {@inheritDoc}
     */
    public function generate(EntityManagerInterface $em, ?object $entity)
    {
        return (int) $em->getConnection()->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function isPostInsertGenerator() : bool
    {
        return true;
    }
}
