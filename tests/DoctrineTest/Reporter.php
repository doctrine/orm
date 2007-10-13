<?php

class DoctrineTest_Reporter
{
    protected $_test;
    
    public function setTestCase(GroupTest $test) 
    {
        $this->_test = $test;
    }
}

