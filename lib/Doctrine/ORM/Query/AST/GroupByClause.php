<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GroupByClause
 *
 * @author robo
 */
class Doctrine_ORM_Query_AST_GroupByClause extends Doctrine_ORM_Query_AST
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
}

