<?php

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\TreeWalkerAdapter;

class RemoveUselessLeftJoinsWalker extends TreeWalkerAdapter
{
    public function walkSelectStatement(SelectStatement $AST)
    {
        $queryComponents = $this->_getQueryComponents();
        $this->removeUnusedJoins($AST);
    }

    private function removeUnusedJoins(SelectStatement $AST)
    {
        $from = $AST->fromClause->identificationVariableDeclarations;
        $fromRoot = reset($from);

        if (!isset($AST->whereClause) || !isset($AST->whereClause->conditionalExpression) || !isset($AST->whereClause->conditionalExpression->simpleConditionalExpression)) {
            return;
        }

        $expr = $AST->whereClause->conditionalExpression->simpleConditionalExpression;

        if (!isset($expr->subselect)) {
            return;
        }

        $subSelect = $expr->subselect;
        $subSelectUsages = $this->findSubSelectUsages($subSelect);

        foreach ($subSelect->subselectFromClause->identificationVariableDeclarations as $declaration) {
            $declaration->joins = $this->filterJoins($declaration->joins, $this->findUnusedJoins($declaration->joins, $subSelectUsages));
        }

        $usages = $this->findSubSelectUsages($AST);
        $fromRoot->joins = $this->filterJoins($fromRoot->joins, $this->findUnusedJoins($fromRoot->joins, $usages));
    }

    private function recursiveAddUsages($usages, $parents)
    {
        foreach ($usages as $id) {
            if (array_key_exists($id, $parents) && !in_array($parents[$id], $usages)) {
                $usages = $this->recursiveAddUsages(array_merge($usages, [$parents[$id]]), $parents);
            }
        }

        return $usages;
    }

    private function findUnusedJoins($joins, $usages)
    {
        $parents = [];
        foreach ($joins as $join) {
            $parents[$join->joinAssociationDeclaration->aliasIdentificationVariable] = $join->joinAssociationDeclaration->joinAssociationPathExpression->identificationVariable;
        }

        $usages = $this->recursiveAddUsages($usages, $parents);

        $unused = [];
        foreach ($joins as $join) {
            if (Query\AST\Join::JOIN_TYPE_LEFT === $join->joinType && !in_array($join->joinAssociationDeclaration->aliasIdentificationVariable, $usages)) {
                $unused[] = $join;
            }
        }

        return array_unique($unused);
    }

    private function filterJoins($joins, $toRemove)
    {
        return array_filter($joins, function (Query\AST\Join $join) use ($toRemove) {
            return !in_array($join, $toRemove);
        });
    }

    private function extractIdentificationVariable($expression)
    {
        if ($expression->simpleArithmeticExpression instanceof Query\AST\Literal) {
            return null;
        }

        return $expression->simpleArithmeticExpression->identificationVariable;
    }

    private function extractIdentificationVariableFromExpression($expr): array
    {
        $usages = [];

        if ($expr instanceof Query\AST\ComparisonExpression) {
            $usages[] = $this->extractIdentificationVariable($expr->leftExpression);
            $usages[] = $this->extractIdentificationVariable($expr->rightExpression);
        } else {
            $usages[] = $this->extractIdentificationVariable($expr->expression);
        }

        return $usages;
    }

    private function findSubSelectUsages($select)
    {
        $usages = [];

        if (isset($select->whereClause)) {
            if ($select->whereClause->conditionalExpression instanceof Query\AST\ConditionalTerm) {
                foreach ($select->whereClause->conditionalExpression->conditionalFactors as $factor) {
                    $expr = $factor->simpleConditionalExpression;
                    $usages = array_merge($usages, $this->extractIdentificationVariableFromExpression($expr));
                }
            } elseif ($select->whereClause->conditionalExpression instanceof Query\AST\ConditionalPrimary) {
                $usages = array_merge($usages, $this->extractIdentificationVariableFromExpression($select->whereClause->conditionalExpression->simpleConditionalExpression));
            }
        }

        if (isset($select->orderByClause)) {
            foreach ($select->orderByClause->orderByItems as $item) {
                if ($item->expression instanceof Query\AST\OrderByItem || $item->expression instanceof Query\AST\PathExpression) {
                    $usages[] = $item->expression->identificationVariable;
                }
            }
        }

        $usages = array_unique($usages);

        return $usages;
    }
}
