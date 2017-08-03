<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

/**
 * Encapsulates the resulting components from a DQL query parsing process that
 * can be serialized.
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @author		Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @link        http://www.doctrine-project.org
 * @since       2.0
 */
class ParserResult
{
    /**
     * The SQL executor used for executing the SQL.
     *
     * @var \Doctrine\ORM\Query\Exec\AbstractSqlExecutor
     */
    private $sqlExecutor;

    /**
     * The ResultSetMapping that describes how to map the SQL result set.
     *
     * @var \Doctrine\ORM\Query\ResultSetMapping
     */
    private $resultSetMapping;

    /**
     * The mappings of DQL parameter names/positions to SQL parameter positions.
     *
     * @var array
     */
    private $parameterMappings = [];

    /**
     * Initializes a new instance of the <tt>ParserResult</tt> class.
     * The new instance is initialized with an empty <tt>ResultSetMapping</tt>.
     */
    public function __construct()
    {
        $this->resultSetMapping = new ResultSetMapping;
    }

    /**
     * Gets the ResultSetMapping for the parsed query.
     *
     * @return ResultSetMapping|null The result set mapping of the parsed query or NULL
     *                               if the query is not a SELECT query.
     */
    public function getResultSetMapping()
    {
        return $this->resultSetMapping;
    }

    /**
     * Sets the ResultSetMapping of the parsed query.
     *
     * @param ResultSetMapping $rsm
     *
     * @return void
     */
    public function setResultSetMapping(ResultSetMapping $rsm)
    {
        $this->resultSetMapping = $rsm;
    }

    /**
     * Sets the SQL executor that should be used for this ParserResult.
     *
     * @param \Doctrine\ORM\Query\Exec\AbstractSqlExecutor $executor
     *
     * @return void
     */
    public function setSqlExecutor($executor)
    {
        $this->sqlExecutor = $executor;
    }

    /**
     * Gets the SQL executor used by this ParserResult.
     *
     * @return \Doctrine\ORM\Query\Exec\AbstractSqlExecutor
     */
    public function getSqlExecutor()
    {
        return $this->sqlExecutor;
    }

    /**
     * Adds a DQL to SQL parameter mapping. One DQL parameter name/position can map to
     * several SQL parameter positions.
     *
     * @param string|integer $dqlPosition
     * @param integer        $sqlPosition
     *
     * @return void
     */
    public function addParameterMapping($dqlPosition, $sqlPosition)
    {
        $this->parameterMappings[$dqlPosition][] = $sqlPosition;
    }

    /**
     * Gets all DQL to SQL parameter mappings.
     *
     * @return array The parameter mappings.
     */
    public function getParameterMappings()
    {
        return $this->parameterMappings;
    }

    /**
     * Gets the SQL parameter positions for a DQL parameter name/position.
     *
     * @param string|integer $dqlPosition The name or position of the DQL parameter.
     *
     * @return array The positions of the corresponding SQL parameters.
     */
    public function getSqlParameterPositions($dqlPosition)
    {
        return $this->parameterMappings[$dqlPosition];
    }
}
