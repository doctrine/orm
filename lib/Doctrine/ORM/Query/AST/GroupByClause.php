<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * Description of GroupByClause
 *
 * @author robo
 */
class GroupByClause extends Node
{
    private $_groupByItems = array();

    public function __construct(array $groupByItems)
    {
        $this->_groupByItems = $groupByItems;
    }

    public function getGroupByItems()
    {
        return $this->_groupByItems;
    }

    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkGroupByClause($this);
    }
}