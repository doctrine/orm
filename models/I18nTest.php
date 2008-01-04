<?php
class I18nTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('title', 'string', 200);
    }
    public function setUp()
    {
        $this->actAs('I18n', array('fields' => array('name', 'title')));
    }
}
