<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

interface DQLCapable
{
    /**
     * A query object is in CLEAN state when it has NO unparsed/unprocessed DQL parts.
     */
    const STATE_CLEAN  = 1;

    /**
     * A query object is in state DIRTY when it has DQL parts that have not yet been
     * parsed/processed. This is automatically defined as DIRTY when addDqlQueryPart
     * is called.
     */
    const STATE_DIRTY = 2;

    /**
     * Returns the corresponding AST for this DQL query.
     *
     * @return \Doctrine\ORM\Query\AST\SelectStatement |
     *         \Doctrine\ORM\Query\AST\UpdateStatement |
     *         \Doctrine\ORM\Query\AST\DeleteStatement
     */
    public function getAST();

    /**
     * Sets a DQL query string.
     *
     * @param string $dqlQuery DQL Query.
     *
     * @return \Doctrine\ORM\AbstractQuery
     */
    public function setDQL($dqlQuery);

    /**
     * Returns the DQL query that is represented by this query object.
     *
     * @return string DQL query.
     */
    public function getDQL();

    /**
     * Method to check if an arbitrary piece of DQL exists
     *
     * @param string $dql Arbitrary piece of DQL to check for.
     *
     * @return boolean
     */
    public function contains($dql);

    /**
     * Returns the state of this query object
     * By default the type is STATE_CLEAN but if it appears any unprocessed DQL
     * part, it is switched to STATE_DIRTY.
     *
     * @return integer The query state.
     */
    public function getState();
}
