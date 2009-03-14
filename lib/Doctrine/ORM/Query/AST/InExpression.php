<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * InExpression ::= StateFieldPathExpression ["NOT"] "IN" "(" (Literal {"," Literal}* | Subselect) ")"
 *
 * @author robo
 */
class InExpression extends Node
{
    private $_pathExpression;
    private $_not = false;
    private $_literals = array();
    private $_subselect;

    public function __construct($pathExpression)
    {
        $this->_pathExpression = $pathExpression;
    }

    public function setLiterals(array $literals)
    {
        $this->_literals = $literals;
    }

    public function getLiterals()
    {
        return $this->_literals;
    }

    public function setSubselect($subselect)
    {
        $this->_subselect = $subselect;
    }

    public function getSubselect()
    {
        return $this->_subselect;
    }

    public function setNot($bool)
    {
        $this->_not = $bool;
    }

    public function getNot()
    {
        return $this->_not;
    }
}

