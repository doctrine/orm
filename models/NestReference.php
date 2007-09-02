<?php
class NestReference extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('parent_id', 'integer', 4, 'primary');
        $this->hasColumn('child_id', 'integer', 4, 'primary');
    }
}
