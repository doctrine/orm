<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLServer2012Platform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\SqlWalker;
use RuntimeException;

use function array_diff;
use function array_keys;
use function count;
use function implode;
use function reset;
use function sprintf;

/**
 * Wraps the query in order to accurately count the root objects.
 *
 * Given a DQL like `SELECT u FROM User u` it will generate an SQL query like:
 * SELECT COUNT(*) (SELECT DISTINCT <id> FROM (<original SQL>))
 *
 * Works with composite keys but cannot deal with queries that have multiple
 * root entities (e.g. `SELECT f, b from Foo, Bar`)
 */
class CountOutputWalker extends SqlWalker
{
    /** @var AbstractPlatform */
    private $platform;

    /** @var ResultSetMapping */
    private $rsm;

    /** @var mixed[] */
    private $queryComponents;

    /**
     * Stores various parameters that are otherwise unavailable
     * because Doctrine\ORM\Query\SqlWalker keeps everything private without
     * accessors.
     *
     * @param Query        $query
     * @param ParserResult $parserResult
     * @param mixed[]      $queryComponents
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->platform        = $query->getEntityManager()->getConnection()->getDatabasePlatform();
        $this->rsm             = $parserResult->getResultSetMapping();
        $this->queryComponents = $queryComponents;

        parent::__construct($query, $parserResult, $queryComponents);
    }

    /**
     * Walks down a SelectStatement AST node, wrapping it in a COUNT (SELECT DISTINCT).
     *
     * Note that the ORDER BY clause is not removed. Many SQL implementations (e.g. MySQL)
     * are able to cache subqueries. By keeping the ORDER BY clause intact, the limitSubQuery
     * that will most likely be executed next can be read from the native SQL cache.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        if ($this->platform instanceof SQLServer2012Platform || $this->platform instanceof SQLServerPlatform) {
            $AST->orderByClause = null;
        }

        $sql = parent::walkSelectStatement($AST);

        if ($AST->groupByClause) {
            return sprintf(
                'SELECT %s AS dctrn_count FROM (%s) dctrn_table',
                $this->platform->getCountExpression('*'),
                $sql
            );
        }

        // Find out the SQL alias of the identifier column of the root entity
        // It may be possible to make this work with multiple root entities but that
        // would probably require issuing multiple queries or doing a UNION SELECT
        // so for now, It's not supported.

        // Get the root entity and alias from the AST fromClause
        $from = $AST->fromClause->identificationVariableDeclarations;
        if (count($from) > 1) {
            throw new RuntimeException('Cannot count query which selects two FROM components, cannot make distinction');
        }

        $fromRoot       = reset($from);
        $rootAlias      = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
        $rootClass      = $this->queryComponents[$rootAlias]['metadata'];
        $rootIdentifier = $rootClass->identifier;

        // For every identifier, find out the SQL alias by combing through the ResultSetMapping
        $sqlIdentifier = [];
        foreach ($rootIdentifier as $property) {
            if (isset($rootClass->fieldMappings[$property])) {
                foreach (array_keys($this->rsm->fieldMappings, $property, true) as $alias) {
                    if ($this->rsm->columnOwnerMap[$alias] === $rootAlias) {
                        $sqlIdentifier[$property] = $alias;
                    }
                }
            }

            if (isset($rootClass->associationMappings[$property])) {
                $joinColumn = $rootClass->associationMappings[$property]['joinColumns'][0]['name'];

                foreach (array_keys($this->rsm->metaMappings, $joinColumn, true) as $alias) {
                    if ($this->rsm->columnOwnerMap[$alias] === $rootAlias) {
                        $sqlIdentifier[$property] = $alias;
                    }
                }
            }
        }

        if (count($rootIdentifier) !== count($sqlIdentifier)) {
            throw new RuntimeException(sprintf(
                'Not all identifier properties can be found in the ResultSetMapping: %s',
                implode(', ', array_diff($rootIdentifier, array_keys($sqlIdentifier)))
            ));
        }

        // Build the counter query
        return sprintf(
            'SELECT %s AS dctrn_count FROM (SELECT DISTINCT %s FROM (%s) dctrn_result) dctrn_table',
            $this->platform->getCountExpression('*'),
            implode(', ', $sqlIdentifier),
            $sql
        );
    }
}
