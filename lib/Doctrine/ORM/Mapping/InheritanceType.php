<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

final class InheritanceType
{
    /**
     * NONE means the class does not participate in an inheritance hierarchy
     * and therefore does not need an inheritance mapping type.
     */
    public const NONE = 'NONE';

    /**
     * JOINED means the class will be persisted according to the rules of
     * <tt>Class Table Inheritance</tt>.
     */
    public const JOINED = 'JOINED';

    /**
     * SINGLE_TABLE means the class will be persisted according to the rules of
     * <tt>Single Table Inheritance</tt>.
     */
    public const SINGLE_TABLE = 'SINGLE_TABLE';

    /**
     * TABLE_PER_CLASS means the class will be persisted according to the rules
     * of <tt>Concrete Table Inheritance</tt>.
     */
    public const TABLE_PER_CLASS = 'TABLE_PER_CLASS';

    /**
     * Will break upon instantiation.
     */
    private function __construct()
    {
    }
}
