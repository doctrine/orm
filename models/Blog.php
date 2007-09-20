<?php
class Blog extends Doctrine_Record
{
    public function setTableDefinition()
    {
    	
    }
    public function setUp()
    {
        $this->loadTemplate('Taggable');
    }
}
class Taggable extends Doctrine_Template
{
    public function setUp()
    {
        $this->hasMany('[Component]TagTemplate as Tag');
    }
}
class TagTemplate extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 100);
        $this->hasColumn('description', 'string');
    }

    public function setUp()
    {
        $this->hasOne('[Component]', array('onDelete' => 'CASCADE'));
    }
}
