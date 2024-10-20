<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\ConditionalExpression;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ConditionalTerm;
use Doctrine\ORM\Query\AST\InListExpression;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\AST\NullComparisonExpression;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\AST\WhereClause;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use RuntimeException;

use function count;
use function in_array;
use function is_string;
use function reset;

/**
 * Appends a condition equivalent to "WHERE IN (:dpid_1, :dpid_2, ...)" to the whereClause of the AST.
 *
 * The parameter namespace (dpid) is defined by
 * the PAGINATOR_ID_ALIAS
 *
 * The HINT_PAGINATOR_HAS_IDS query hint indicates whether there are
 * any ids in the parameter at all.
 */
class WhereInWalker extends TreeWalkerAdapter
{
    /**
     * ID Count hint name.
     */
    public const HINT_PAGINATOR_HAS_IDS = 'doctrine.paginator_has_ids';

    /**
     * Primary key alias for query.
     */
    public const PAGINATOR_ID_ALIAS = 'dpid';

    public function walkSelectStatement(SelectStatement $selectStatement): void
    {
        // Get the root entity and alias from the AST fromClause
        $from = $selectStatement->fromClause->identificationVariableDeclarations;

        if (count($from) > 1) {
            throw new RuntimeException('Cannot count query which selects two FROM components, cannot make distinction');
        }

        $fromRoot            = reset($from);
        $rootAlias           = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
        $rootClass           = $this->getMetadataForDqlAlias($rootAlias);
        $identifierFieldName = $rootClass->getSingleIdentifierFieldName();

        $pathType = PathExpression::TYPE_STATE_FIELD;
        if (isset($rootClass->associationMappings[$identifierFieldName])) {
            $pathType = PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;
        }

        $pathExpression       = new PathExpression(PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION, $rootAlias, $identifierFieldName);
        $pathExpression->type = $pathType;

        $hasIds = $this->_getQuery()->getHint(self::HINT_PAGINATOR_HAS_IDS);

        if ($hasIds) {
            $arithmeticExpression                             = new ArithmeticExpression();
            $arithmeticExpression->simpleArithmeticExpression = new SimpleArithmeticExpression(
                [$pathExpression],
            );
            $expression                                       = new InListExpression(
                $arithmeticExpression,
                [new InputParameter(':' . self::PAGINATOR_ID_ALIAS)],
            );
        } else {
            $expression = new NullComparisonExpression($pathExpression);
        }

        $conditionalPrimary                              = new ConditionalPrimary();
        $conditionalPrimary->simpleConditionalExpression = $expression;
        if ($this->hasSubselect($selectStatement) && $selectStatement->whereClause) {
            if ($selectStatement->whereClause->conditionalExpression instanceof ConditionalTerm) {
                $selectStatement->whereClause->conditionalExpression->conditionalFactors[] = $conditionalPrimary;
            } elseif ($selectStatement->whereClause->conditionalExpression instanceof ConditionalPrimary) {
                $selectStatement->whereClause->conditionalExpression = new ConditionalExpression(
                    [
                        new ConditionalTerm(
                            [
                                $selectStatement->whereClause->conditionalExpression,
                                $conditionalPrimary,
                            ],
                        ),
                    ],
                );
            } else {
                $tmpPrimary                                          = new ConditionalPrimary();
                $tmpPrimary->conditionalExpression                   = $selectStatement->whereClause->conditionalExpression;
                $selectStatement->whereClause->conditionalExpression = new ConditionalTerm(
                    [
                        $tmpPrimary,
                        $conditionalPrimary,
                    ],
                );
            }
        } else {
            $selectStatement->whereClause = new WhereClause(
                new ConditionalExpression(
                    [new ConditionalTerm([$conditionalPrimary])],
                ),
            );
        }

        if ($this->clausesAreUsingOnlyFromIdnetifications($selectStatement)) {
            foreach ($selectStatement->fromClause->identificationVariableDeclarations as $f) {
                $f->joins = [];
            }
        }
    }

    private function clausesAreUsingOnlyFromIdnetifications(SelectStatement $selectStatement): bool
    {
        $fromAliases = [];
        foreach ($selectStatement->fromClause->identificationVariableDeclarations as $f) {
            $fromAliases[] = $f->rangeVariableDeclaration->aliasIdentificationVariable;
        }

        foreach ($selectStatement->selectClause->selectExpressions as $selectExpression) {
            if (! $this->isExpressionExistsInFromClause($selectExpression->expression, $fromAliases)) {
                return false;
            }
        }

        if ($selectStatement->groupByClause !== null) {
            foreach ($selectStatement->groupByClause->groupByItems as $groupByItem) {
                if (! $this->isExpressionExistsInFromClause($groupByItem->expression, $fromAliases)) {
                    return false;
                }
            }
        }

        if ($selectStatement->havingClause !== null) {
            return false;
        }

        if ($selectStatement->orderByClause !== null) {
            foreach ($selectStatement->orderByClause->orderByItems as $orderByItem) {
                if (! $this->isExpressionExistsInFromClause($orderByItem->expression, $fromAliases)) {
                    return false;
                }
            }
        }

        return true;
    }

    /** @param string[] $fromAliases */
    private function isExpressionExistsInFromClause(mixed $expression, array $fromAliases): bool
    {
        $expressionIdentification = null;

        if ($expression instanceof PathExpression) {
            $expressionIdentification = $expression->identificationVariable;
        } elseif (is_string($expression)) {
            $expressionIdentification = $expression;
        }

        if ($expressionIdentification === null) {
            return false;
        }

        return in_array($expression, $fromAliases, true);
    }

    private function hasSubselect(SelectStatement $selectStatement): bool
    {
        foreach ($selectStatement->selectClause->selectExpressions as $selectExpression) {
            if ($selectExpression instanceof SelectExpression && $selectExpression->expression instanceof Subselect) {
                return true;
            }
        }

        return false;
    }
}
