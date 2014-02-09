<?php
/**
 * Doctrine ORM
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;

/**
 * Wraps the query in order to select root entity IDs for pagination.
 *
 * Given a DQL like `SELECT u FROM User u` it will generate an SQL query like:
 * SELECT DISTINCT <id> FROM (<original SQL>) LIMIT x OFFSET y
 *
 * Works with composite keys but cannot deal with queries that have multiple
 * root entities (e.g. `SELECT f, b from Foo, Bar`)
 *
 * @author Sander Marechal <s.marechal@jejik.com>
 */
class LimitSubqueryOutputWalker extends SqlWalker
{
    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $platform;

    /**
     * @var \Doctrine\ORM\Query\ResultSetMapping
     */
    private $rsm;

    /**
     * @var array
     */
    private $queryComponents;

    /**
     * @var int
     */
    private $firstResult;

    /**
     * @var int
     */
    private $maxResults;

    /**
     * Constructor.
     *
     * Stores various parameters that are otherwise unavailable
     * because Doctrine\ORM\Query\SqlWalker keeps everything private without
     * accessors.
     *
     * @param \Doctrine\ORM\Query              $query
     * @param \Doctrine\ORM\Query\ParserResult $parserResult
     * @param array                            $queryComponents
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->platform = $query->getEntityManager()->getConnection()->getDatabasePlatform();
        $this->rsm = $parserResult->getResultSetMapping();
        $this->queryComponents = $queryComponents;

        // Reset limit and offset
        $this->firstResult = $query->getFirstResult();
        $this->maxResults = $query->getMaxResults();
        $query->setFirstResult(null)->setMaxResults(null);

        parent::__construct($query, $parserResult, $queryComponents);
    }

    /**
     * Walks down a SelectStatement AST node, wrapping it in a SELECT DISTINCT.
     *
     * @param SelectStatement $AST
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        if ($this->platform instanceof PostgreSqlPlatform) {
            // Set every select expression as visible(hidden = false) to
            // make $AST to have scalar mappings properly
            $hiddens = array();
            foreach ($AST->selectClause->selectExpressions as $idx => $expr) {
                $hiddens[$idx] = $expr->hiddenAliasResultVariable;
                $expr->hiddenAliasResultVariable = false;
            }

            $innerSql = parent::walkSelectStatement($AST);

            // Restore hiddens
            foreach ($AST->selectClause->selectExpressions as $idx => $expr) {
                $expr->hiddenAliasResultVariable = $hiddens[$idx];
            }
        } else {
            $innerSql = parent::walkSelectStatement($AST);
        }


        // Find out the SQL alias of the identifier column of the root entity.
        // It may be possible to make this work with multiple root entities but that
        // would probably require issuing multiple queries or doing a UNION SELECT.
        // So for now, it's not supported.

        // Get the root entity and alias from the AST fromClause.
        $from = $AST->fromClause->identificationVariableDeclarations;
        if (count($from) !== 1) {
            throw new \RuntimeException("Cannot count query which selects two FROM components, cannot make distinction");
        }

        $rootAlias      = $from[0]->rangeVariableDeclaration->aliasIdentificationVariable;
        $rootClass      = $this->queryComponents[$rootAlias]['metadata'];
        $rootIdentifier = $rootClass->identifier;

        // For every identifier, find out the SQL alias by combing through the ResultSetMapping
        $sqlIdentifier = array();
        foreach ($rootIdentifier as $property) {
            if (isset($rootClass->fieldMappings[$property])) {
                foreach (array_keys($this->rsm->fieldMappings, $property) as $alias) {
                    if ($this->rsm->columnOwnerMap[$alias] == $rootAlias) {
                        $sqlIdentifier[$property] = $alias;
                    }
                }
            }

            if (isset($rootClass->associationMappings[$property])) {
                $joinColumn = $rootClass->associationMappings[$property]['joinColumns'][0]['name'];

                foreach (array_keys($this->rsm->metaMappings, $joinColumn) as $alias) {
                    if ($this->rsm->columnOwnerMap[$alias] == $rootAlias) {
                        $sqlIdentifier[$property] = $alias;
                    }
                }
            }
        }

        if (count($rootIdentifier) != count($sqlIdentifier)) {
            throw new \RuntimeException(sprintf(
                'Not all identifier properties can be found in the ResultSetMapping: %s',
                implode(', ', array_diff($rootIdentifier, array_keys($sqlIdentifier)))
            ));
        }

        // Build the counter query
        $sql = sprintf('SELECT DISTINCT %s FROM (%s) dctrn_result',
            implode(', ', $sqlIdentifier), $innerSql);

        // http://www.doctrine-project.org/jira/browse/DDC-1958
        $sql = $this->preserveSqlOrdering($AST, $sqlIdentifier, $innerSql, $sql);

        // Apply the limit and offset.
        $sql = $this->platform->modifyLimitQuery(
            $sql, $this->maxResults, $this->firstResult
        );

        // Add the columns to the ResultSetMapping. It's not really nice but
        // it works. Preferably I'd clear the RSM or simply create a new one
        // but that is not possible from inside the output walker, so we dirty
        // up the one we have.
        foreach ($sqlIdentifier as $property => $alias) {
            $this->rsm->addScalarResult($alias, $property);
        }

        return $sql;
    }

    /**
     * Generates new SQL for Postgresql or Oracle if necessary.
     *
     * @param SelectStatement $AST
     * @param array           $sqlIdentifier
     * @param string          $innerSql
     * @param string          $sql
     *
     * @return void
     */
    public function preserveSqlOrdering(SelectStatement $AST, array $sqlIdentifier, $innerSql, $sql)
    {
        // For every order by, find out the SQL alias by inspecting the ResultSetMapping.
        $sqlOrderColumns = array();
        $orderBy         = array();
        if (isset($AST->orderByClause)) {
            foreach ($AST->orderByClause->orderByItems as $item) {
                $possibleAliases = (is_object($item->expression))
                    ? array_keys($this->rsm->fieldMappings, $item->expression->field)
                    : array_keys($this->rsm->scalarMappings, $item->expression);

                foreach ($possibleAliases as $alias) {
                    if (!is_object($item->expression) || $this->rsm->columnOwnerMap[$alias] == $item->expression->identificationVariable) {
                        $sqlOrderColumns[] = $alias;
                        $orderBy[]         = $alias . ' ' . $item->type;
                        break;
                    }
                }
            }
            //remove identifier aliases
            $sqlOrderColumns = array_diff($sqlOrderColumns, $sqlIdentifier);
        }

        if (count($orderBy)) {
            $sql = sprintf(
                'SELECT DISTINCT %s FROM (%s) dctrn_result ORDER BY %s',
                implode(', ', array_merge($sqlIdentifier, $sqlOrderColumns)),
                $innerSql,
                implode(', ', $orderBy)
            );
        }

        return $sql;
    }
}
