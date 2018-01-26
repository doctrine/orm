<?php

declare(strict_types=1);

namespace Doctrine\ORM\Sequencing;

use Doctrine\ORM\EntityManagerInterface;

interface Generator
{
    /**
     * Generates an identifier for an entity.
     *
     * @param object $entity
     *
     * @return \Generator
     */
    public function generate(EntityManagerInterface $em, $entity);

    /**
     * Gets whether this generator is a post-insert generator which means that
     * {@link generate()} must be called after the entity has been inserted
     * into the database.
     *
     * By default, this method returns FALSE. Generators that have this requirement
     * must override this method and return TRUE.
     *
     * @return bool
     */
    public function isPostInsertGenerator();
}
