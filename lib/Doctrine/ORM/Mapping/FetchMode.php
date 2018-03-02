<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

final class FetchMode
{
    /**
     * Specifies that an association is to be fetched when it is first accessed.
     */
    public const LAZY = 'LAZY';

    /**
     * Specifies that an association is to be fetched when the owner of the
     * association is fetched.
     */
    public const EAGER = 'EAGER';

    /**
     * Specifies that an association is to be fetched lazy (on first access) and that
     * commands such as Collection#count, Collection#slice are issued directly against
     * the database if the collection is not yet initialized.
     */
    public const EXTRA_LAZY = 'EXTRA_LAZY';

    /**
     * Will break upon instantiation.
     */
    private function __construct()
    {
    }
}
