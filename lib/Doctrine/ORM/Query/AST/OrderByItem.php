<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * OrderByItem ::= StateFieldPathExpression ["ASC" | "DESC"]
 *
 * @author robo
 */
class OrderByItem extends Node
{
    private $_pathExpr;
    private $_asc;
    private $_desc;

    public function __construct($pathExpr)
    {
        $this->_pathExpr = $pathExpr;
    }

    public function getStateFieldPathExpression()
    {
        return $this->_pathExpr;
    }

    public function setAsc($bool)
    {
        $this->_asc = $bool;
    }

    public function isAsc()
    {
        return $this->_asc;
    }

    public function setDesc($bool)
    {
        $this->_desc = $bool;
    }

    public function isDesc()
    {
        return $this->_desc;
    }
}