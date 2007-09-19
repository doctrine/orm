<?php
class gnatEmail extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('address', 'string', 150);
    }
    
    
}
