<?php
class MigrationTest extends Doctrine_Record
{
    public function setTableDefinition() 
    {
        $this->hasColumn('field1', 'string');
    }
}