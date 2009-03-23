<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * Description of InputParameter
 *
 * @author robo
 */
class InputParameter extends Node
{
    private $_isNamed;
    private $_position;
    private $_name;

    public function __construct($value)
    {
        if (strlen($value) == 1) {
            throw new \InvalidArgumentException("Invalid parameter format.");
        }

        $param = substr($value, 1);
        $this->_isNamed = ! is_numeric($param);
        if ($this->_isNamed) {
            $this->_name = $param;
        } else {
            $this->_position = $param;
        }
    }

    public function isNamed()
    {
        return $this->_isNamed;
    }

    public function isPositional()
    {
        return ! $this->_isNamed;
    }

    public function getName()
    {
        return $this->_name;
    }
    
    public function getPosition()
    {
        return $this->_position;
    }

    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkInputParameter($this);
    }
}