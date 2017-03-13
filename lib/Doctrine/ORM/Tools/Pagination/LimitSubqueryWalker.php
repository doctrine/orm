<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ToManyAssociationMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Doctrine\ORM\Query\AST\Functions\IdentityFunction;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;

/**
 * Replaces the selectClause of the AST with a SELECT DISTINCT root.id equivalent.
 *
 * @category    DoctrineExtensions
 * @package     DoctrineExtensions\Paginate
 * @author      David Abdemoulaie <dave@hobodave.com>
 * @copyright   Copyright (c) 2010 David Abdemoulaie (http://hobodave.com/)
 * @license     http://hobodave.com/license.txt New BSD License
 */
class LimitSubqueryWalker extends TreeWalkerAdapter
{
    /**
     * ID type hint.
     */
    const IDENTIFIER_TYPE = 'doctrine_paginator.id.type';

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
     * @param SelectStatement $AST
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        $queryComponents = $this->getQueryComponents();
        // Get the root entity and alias from the AST fromClause
        $from      = $AST->fromClause->identificationVariableDeclarations;
        $fromRoot  = reset($from);
        $rootAlias = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
        $rootClass = $queryComponents[$rootAlias]['metadata'];
        $selectExpressions = [];

        $this->validate($AST);

        foreach ($queryComponents as $dqlAlias => $qComp) {
            // Preserve mixed data in query for ordering.
            if (isset($qComp['resultVariable'])) {
                $selectExpressions[] = new SelectExpression($qComp['resultVariable'], $dqlAlias);
                continue;
            }
        }

        $identifier = $rootClass->getSingleIdentifierFieldName();

        if (isset($rootClass->associationMappings[$identifier])) {
            throw new \RuntimeException(
                "Paginating an entity with foreign key as identifier only works when using the Output Walkers. " .
                "Call Paginator#setUseOutputWalkers(true) before iterating the paginator."
            );
        }

        $this->getQuery()->setHint(self::IDENTIFIER_TYPE, $rootClass->getProperty($identifier)->getType());

        $pathExpression = new PathExpression(
            PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            $rootAlias,
            $identifier
        );

        $pathExpression->type = PathExpression::TYPE_STATE_FIELD;

        array_unshift($selectExpressions, new SelectExpression($pathExpression, '_dctrn_id'));

        $AST->selectClause->selectExpressions = $selectExpressions;

        if (isset($AST->orderByClause)) {
            foreach ($AST->orderByClause->orderByItems as $item) {
                if ( ! $item->expression instanceof PathExpression) {
                    continue;
                }

                $AST->selectClause->selectExpressions[] = new SelectExpression(
                    $this->createSelectExpressionItem($item->expression),
                    '_dctrn_ord' . $this->aliasCounter++
                );
            }
        }

        $AST->selectClause->isDistinct = true;
    }

    /**
     * Validate the AST to ensure that this walker is able to properly manipulate it.
     *
     * @param SelectStatement $AST
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
                        throw new \RuntimeException(
                            "Cannot select distinct identifiers from query with LIMIT and ORDER BY on a column from a "
                            . "fetch joined to-many association. Use output walkers."
                        );
                    }
                }
            }
        }
    }

    /**
     * Retrieve either an IdentityFunction (IDENTITY(u.assoc)) or a state field (u.name).
     *
     * @param \Doctrine\ORM\Query\AST\PathExpression $pathExpression
     *
     * @return \Doctrine\ORM\Query\AST\Functions\IdentityFunction
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
