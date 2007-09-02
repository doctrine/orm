<?php
class FooForeignlyOwnedWithPk extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 200);
    }
    public function setUp()
    {
        $this->hasOne('FooRecord', array('local' => 'id', 'foreign' => 'id'));
    }
}
