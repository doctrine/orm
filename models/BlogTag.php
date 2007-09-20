<?php 
class BlogTag extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 100);
        $this->hasColumn('description', 'string');
    }
    public function setUp()
    {
        $this->hasOne('Blog', array('onDelete' => 'CASCADE'));
    }
}
