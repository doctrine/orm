<?php

/**
 * Doctrine ORM
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE. This license can also be viewed
 * at http://hobodave.com/license.txt
 *
 * @category    DoctrineExtensions
 * @package     DoctrineExtensions\Paginate
 * @author      David Abdemoulaie <dave@hobodave.com>
 * @copyright   Copyright (c) 2010 David Abdemoulaie (http://hobodave.com/)
 * @license     http://hobodave.com/license.txt New BSD License
 */

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\DBAL\Types\Type,
    Doctrine\ORM\Query\TreeWalkerAdapter,
    Doctrine\ORM\Query\AST\SelectStatement,
    Doctrine\ORM\Query\AST\SelectExpression,
    Doctrine\ORM\Query\AST\PathExpression,
    Doctrine\ORM\Query\AST\AggregateExpression;

/**
 * Replaces the selectClause of the AST with a SELECT DISTINCT root.id equivalent
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
     * ID type hint
     */
    const IDENTIFIER_TYPE = 'doctrine_paginator.id.type';

    /**
     * @var int Counter for generating unique order column aliases
     */
    private $_aliasCounter = 0;

    /**
     * Walks down a SelectStatement AST node, modifying it to retrieve DISTINCT ids
     * of the root Entity
     *
     * @param SelectStatement $AST
     * @return void
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        $parent = null;
        $parentName = null;
        $selectExpressions = array();

        foreach ($this->_getQueryComponents() AS $dqlAlias => $qComp) {
            // preserve mixed data in query for ordering
            if (isset($qComp['resultVariable'])) {
                $selectExpressions[] = new SelectExpression($qComp['resultVariable'], $dqlAlias);
                continue;
            }

            if ($qComp['parent'] === null && $qComp['nestingLevel'] == 0) {
                $parent = $qComp;
                $parentName = $dqlAlias;
                continue;
            }
        }

        $identifier = $parent['metadata']->getSingleIdentifierFieldName();
        $this->_getQuery()->setHint(
            self::IDENTIFIER_TYPE,
            Type::getType($parent['metadata']->getTypeOfField($identifier))
        );

        $pathExpression = new PathExpression(
            PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            $parentName,
            $identifier
        );
        $pathExpression->type = PathExpression::TYPE_STATE_FIELD;

        array_unshift($selectExpressions, new SelectExpression($pathExpression, '_dctrn_id'));
        $AST->selectClause->selectExpressions = $selectExpressions;

        if (isset($AST->orderByClause)) {
            foreach ($AST->orderByClause->orderByItems as $item) {
                if ($item->expression instanceof PathExpression) {
                    $pathExpression = new PathExpression(
                        PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                        $item->expression->identificationVariable,
                        $item->expression->field
                    );
                    $pathExpression->type = PathExpression::TYPE_STATE_FIELD;
                    $AST->selectClause->selectExpressions[] = new SelectExpression(
                        $pathExpression,
                    	'_dctrn_ord' . $this->_aliasCounter++
                    );
                }
            }
        }

        $AST->selectClause->isDistinct = true;
    }

}



