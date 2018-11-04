<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ToManyAssociationMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\Functions\IdentityFunction;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use RuntimeException;
use function count;
use function is_string;
use function reset;

/**
 * Replaces the selectClause of the AST with a SELECT DISTINCT root.id equivalent.
 */
class LimitSubqueryWalker extends TreeWalkerAdapter
{
    /**
     * ID type hint.
     */
    public const IDENTIFIER_TYPE = 'doctrine_paginator.id.type';

    /**
     * Counter for generating unique order column aliases.
     *
     * @var int
     */
    private $aliasCounter = 0;

    /**
     * Walks down a SelectStatement AST node, modifying it to retrieve DISTINCT ids
     * of the root Entity.
     *
     * @throws RuntimeException
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        $queryComponents = $this->getQueryComponents();
        // Get the root entity and alias from the AST fromClause
        $from      = $AST->fromClause->identificationVariableDeclarations;
        $fromRoot  = reset($from);
        $rootAlias = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
        $rootClass = $queryComponents[$rootAlias]['metadata'];

        $this->validate($AST);
        $property = $rootClass->getProperty($rootClass->getSingleIdentifierFieldName());

        if ($property instanceof AssociationMetadata) {
            throw new RuntimeException(
                'Paginating an entity with foreign key as identifier only works when using the Output Walkers. ' .
                'Call Paginator#setUseOutputWalkers(true) before iterating the paginator.'
            );
        }

        $this->getQuery()->setHint(self::IDENTIFIER_TYPE, $property->getType());

        $pathExpression = new PathExpression(
            PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            $rootAlias,
            $property->getName()
        );

        $pathExpression->type = PathExpression::TYPE_STATE_FIELD;

        $AST->selectClause->selectExpressions = [new SelectExpression($pathExpression, '_dctrn_id')];
        $AST->selectClause->isDistinct        = true;

        if (! isset($AST->orderByClause)) {
            return;
        }

        foreach ($AST->orderByClause->orderByItems as $item) {
            if ($item->expression instanceof PathExpression) {
                $AST->selectClause->selectExpressions[] = new SelectExpression(
                    $this->createSelectExpressionItem($item->expression),
                    '_dctrn_ord' . $this->aliasCounter++
                );

                continue;
            }

            if (is_string($item->expression) && isset($queryComponents[$item->expression])) {
                $qComp = $queryComponents[$item->expression];

                if (isset($qComp['resultVariable'])) {
                    $AST->selectClause->selectExpressions[] = new SelectExpression(
                        $qComp['resultVariable'],
                        $item->expression
                    );
                }
            }
        }
    }

    /**
     * Validate the AST to ensure that this walker is able to properly manipulate it.
     */
    private function validate(SelectStatement $AST)
    {
        // Prevent LimitSubqueryWalker from being used with queries that include
        // a limit, a fetched to-many join, and an order by condition that
        // references a column from the fetch joined table.
        $queryComponents = $this->getQueryComponents();
        $query           = $this->getQuery();
        $from            = $AST->fromClause->identificationVariableDeclarations;
        $fromRoot        = reset($from);

        if ($query instanceof Query && $query->getMaxResults() && $AST->orderByClause && count($fromRoot->joins)) {
            // Check each orderby item.
            // TODO: check complex orderby items too...
            foreach ($AST->orderByClause->orderByItems as $orderByItem) {
                $expression = $orderByItem->expression;

                if ($expression instanceof PathExpression && isset($queryComponents[$expression->identificationVariable])) {
                    $queryComponent = $queryComponents[$expression->identificationVariable];

                    if (isset($queryComponent['parent']) && $queryComponent['relation'] instanceof ToManyAssociationMetadata) {
                        throw new RuntimeException(
                            'Cannot select distinct identifiers from query with LIMIT and ORDER BY on a column from a '
                            . 'fetch joined to-many association. Use output walkers.'
                        );
                    }
                }
            }
        }
    }

    /**
     * Retrieve either an IdentityFunction (IDENTITY(u.assoc)) or a state field (u.name).
     *
     * @return IdentityFunction
     */
    private function createSelectExpressionItem(PathExpression $pathExpression)
    {
        if ($pathExpression->type === PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION) {
            $identity = new IdentityFunction('identity');

            $identity->pathExpression = clone $pathExpression;

            return $identity;
        }

        return clone $pathExpression;
    }
}
