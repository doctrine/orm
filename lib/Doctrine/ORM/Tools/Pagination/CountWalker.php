<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;
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

    /**
     * Walks down a SelectStatement AST node, modifying it to retrieve a COUNT.
     *
     * @throws RuntimeException
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        if ($AST->havingClause) {
            throw new RuntimeException('Cannot count query that uses a HAVING clause. Use the output walkers for pagination');
        }

        $queryComponents = $this->getQueryComponents();
        // Get the root entity and alias from the AST fromClause
        $from = $AST->fromClause->identificationVariableDeclarations;

        if (count($from) > 1) {
            throw new RuntimeException('Cannot count query which selects two FROM components, cannot make distinction');
        }

        $fromRoot  = reset($from);
        $rootAlias = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
        $rootClass = $queryComponents[$rootAlias]['metadata'];
        $property  = $rootClass->getProperty($rootClass->getSingleIdentifierFieldName());
        $pathType  = PathExpression::TYPE_STATE_FIELD;

        if ($property instanceof AssociationMetadata) {
            $pathType = $property instanceof ToOneAssociationMetadata
                ? PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION
                : PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION;
        }

        $pathExpression = new PathExpression(
            PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            $rootAlias,
            $property->getName()
        );

        $pathExpression->type = $pathType;

        $distinct = $this->getQuery()->getHint(self::HINT_DISTINCT);

        $AST->selectClause->selectExpressions = [new SelectExpression(
            new AggregateExpression('count', $pathExpression, $distinct),
            null
        ),
        ];

        // ORDER BY is not needed, only increases query execution through unnecessary sorting.
        $AST->orderByClause = null;
    }
}
