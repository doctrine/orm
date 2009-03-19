<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * OrderByClause ::= "ORDER" "BY" OrderByItem {"," OrderByItem}*
 *
 * @author robo
 */
class OrderByClause extends Node
{
    private $_orderByItems = array();

    public function __construct(array $orderByItems)
    {
        $this->_orderByItems = $orderByItems;
    }

    public function getOrderByItems()
    {
        return $this->_orderByItems;
    }
}