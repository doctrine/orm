<?php
class FieldNameTest extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('someColumn', 'string', 200, array('default' => 'some string'));
        $this->hasColumn('someEnum', 'enum', 4, array('default' => 'php', 'values' => array('php', 'java', 'python')));
        $this->hasColumn('someArray', 'array', 100, array('default' => array()));
        $this->hasColumn('someObject', 'object', 200, array('default' => new stdClass));
        $this->hasColumn('someInt', 'integer', 20, array('default' => 11));
    }
}
