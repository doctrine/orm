<?php
class ConcreteInheritanceTestParent extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
    }
}

class ConcreteInheritanceTestChild extends ConcreteInheritanceTestParent
{
    public function setTableDefinition()
    {
        $this->hasColumn('age', 'integer');
        
        parent::setTableDefinition();
    }
}
