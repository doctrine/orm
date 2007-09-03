<?php
class Cms_Category extends Doctrine_Record 
{
 
	public function setUp() 
    {
		$this->hasMany('Cms_CategoryLanguages as langs', array('local' => 'id', 'foreign' => 'category_id'));
	}
 
	public function setTableDefinition() 
    {
		$this->hasColumn('created', 'timestamp');
		$this->hasColumn('parent', 'integer', 11);
		$this->hasColumn('position', 'integer', 3);
		$this->hasColumn('active', 'integer', 11);
		$this->option('collate', 'utf8_unicode_ci');
		$this->option('charset', 'utf8');
		$this->option('type', 'INNODB');
		$this->index('index_parent', array('fields' => array('parent')));
	}
}
