<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Entity;

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * A swappable persister context to use as a container for the current
 * generated query/resultSetMapping/type binding information.
 *
 * This class is a utility class to be used only by the persister API
 *
 * This object is highly mutable due to performance reasons. Same reasoning
 * behind its properties being public.
 */
class CachedPersisterContext
{
    /**
     * The SELECT column list SQL fragment used for querying entities by this persister.
     * This SQL fragment is only generated once per request, if at all.
     */
    public string|null $selectColumnListSql = null;

    /**
     * The JOIN SQL fragment used to eagerly load all many-to-one and one-to-one
     * associations configured as FETCH_EAGER, as well as all inverse one-to-one associations.
     */
    public string|null $selectJoinSql = null;

    /**
     * Counter for creating unique SQL table and column aliases.
     */
    public int $sqlAliasCounter = 0;

    /**
     * Map from class names (FQCN) to the corresponding generated SQL table aliases.
     *
     * @var array<class-string, string>
     */
    public array $sqlTableAliases = [];

    public function __construct(
        /**
         * Metadata object that describes the mapping of the mapped entity class.
         */
        public ClassMetadata $class,
        /**
         * ResultSetMapping that is used for all queries. Is generated lazily once per request.
         */
        public ResultSetMapping $rsm,
        /**
         * Whether this persistent context is considering limit operations applied to the selection queries
         */
        public bool $handlesLimits,
    ) {
    }
}
