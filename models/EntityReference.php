<?php
class EntityReference extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('entity1', 'integer', null, 'primary');
        $this->hasColumn('entity2', 'integer', null, 'primary');
        //$this->setPrimaryKey(array('entity1', 'entity2'));
    }
}

