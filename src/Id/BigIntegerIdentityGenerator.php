<?php

declare(strict_types=1);

namespace Doctrine\ORM\Id;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Id generator that obtains IDs from special "identity" columns. These are columns
 * that automatically get a database-generated, auto-incremented identifier on INSERT.
 * This generator obtains the last insert id after such an insert.
 */
class BigIntegerIdentityGenerator extends AbstractIdGenerator
{
    /**
     * The name of the sequence to pass to lastInsertId(), if any.
     *
     * @var string|null
     */
    private $sequenceName;

    /**
     * @param string|null $sequenceName The name of the sequence to pass to lastInsertId()
     *                                  to obtain the last generated identifier within the current
     *                                  database session/connection, if any.
     */
    public function __construct($sequenceName = null)
    {
        if ($sequenceName !== null) {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/issues/8850',
                'Passing a sequence name to the IdentityGenerator is deprecated in favor of using %s. $sequenceName will be removed in ORM 3.0',
                SequenceGenerator::class
            );
        }

        $this->sequenceName = $sequenceName;
    }

    /**
     * {@inheritDoc}
     */
    public function generateId(EntityManagerInterface $em, $entity)
    {
        return (string) $em->getConnection()->lastInsertId($this->sequenceName);
    }

    /**
     * {@inheritDoc}
     */
    public function isPostInsertGenerator()
    {
        return true;
    }
}
