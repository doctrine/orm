<?php
class TestRecord extends Doctrine_Record 
{
    public function setTableDefinition()
    {
        $this->setTableName('test');
    }
}
