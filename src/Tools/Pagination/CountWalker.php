<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\ORM\Query\AST\AggregateExpression;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use RuntimeException;

use function count;
use function reset;

/**
 * Replaces the selectClause of the AST with a COUNT statement.
 */
class CountWalker extends TreeWalkerAdapter
{
    /**
     * Distinct mode hint name.
     */
    public const HINT_DISTINCT = 'doctrine_paginator.distinct';

    /** @internal */
    public const HINT_DROP_GROUP_BY_CLAUSE = 'doctrine_paginator.drop_group_by_clause';

    public function walkSelectStatement(SelectStatement $selectStatement): void
    {
        if ($selectStatement->havingClause) {
            throw new RuntimeException('Cannot count query that uses a HAVING clause. Use the output walkers for pagination');
        }

        // Get the root entity and alias from the AST fromClause
        $from = $selectStatement->fromClause->identificationVariableDeclarations;

        if (count($from) > 1) {
            throw new RuntimeException('Cannot count query which selects two FROM components, cannot make distinction');
        }

        $dropGroupByClause = $this->_getQuery()->getHint(self::HINT_DROP_GROUP_BY_CLAUSE);
        $distinct          = $this->_getQuery()->getHint(self::HINT_DISTINCT);

        $countPathExpressionOrLiteral = '*';
        if ($distinct) {
            $fromRoot  = reset($from);
            $rootAlias = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
            $rootClass = $this->getMetadataForDqlAlias($rootAlias);
            // For broader compatibility, only a single id column is supported for the SELECT COUNT(DISTINCT id) query,
            // despite the fact that multiple popular databases do support running such queries with multiple id columns
            // (though with a varying syntax).
            $identifierFieldName = $rootClass->getSingleIdentifierFieldName();

            $pathType = PathExpression::TYPE_STATE_FIELD;
            if (isset($rootClass->associationMappings[$identifierFieldName])) {
                $pathType = PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;
            }

            $countPathExpressionOrLiteral       = new PathExpression(
                PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                $rootAlias,
                $identifierFieldName,
            );
            $countPathExpressionOrLiteral->type = $pathType;
        }

        $selectStatement->selectClause->selectExpressions = [
            new SelectExpression(
                new AggregateExpression('count', $countPathExpressionOrLiteral, $distinct),
                null,
            ),
        ];

        // The GROUP BY clause has a mixed support. There are however cases when the clause produces records count
        // that is exactly equal to running SELECT COUNT(*) / SELECT COUNT(DISTINCT id), provided that the clause
        // is removed from the query.
        if ($dropGroupByClause) {
            $selectStatement->groupByClause = null;
        }

        // ORDER BY is not needed, only increases query execution through unnecessary sorting.
        $selectStatement->orderByClause = null;
    }
}
