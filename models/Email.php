<?php
class Email extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('address', 'string', 150, 'email|unique');
    }
    
    
}
