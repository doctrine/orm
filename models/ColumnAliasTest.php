<?php
class ColumnAliasTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('column1 as alias1', 'string', 200);
        $this->hasColumn('column2 as alias2', 'integer', 11);
    }
    public function setUp() 
    {
    }
}
