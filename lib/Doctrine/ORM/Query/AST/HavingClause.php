<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * Description of HavingClause
 *
 * @author robo
 */
class HavingClause extends Node
{
    private $_conditionalExpression;

    public function __construct($conditionalExpression)
    {
        $this->_conditionalExpression = $conditionalExpression;
    }

    public function getConditionalExpression()
    {
        return $this->_conditionalExpression;
    }
}