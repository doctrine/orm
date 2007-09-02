<?php
class FooBarRecord extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('fooId', 'integer', null, array('primary' => true));
        $this->hasColumn('barId', 'integer', null, array('primary' => true));
    }
}
