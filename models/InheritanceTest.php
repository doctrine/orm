<?php
class InheritanceTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
        $this->hasColumn('type', 'string');
        
        $this->setSubclasses(array('InheritanceChildTest' => array('type' => 'type 1'), 
                                   'InheritanceChild2Test' => array('type' => 'type 2')));
    }
}

class InheritanceChildTest extends InheritanceTest
{ }

class InheritanceChild2Test extends InheritanceTest
{ }

