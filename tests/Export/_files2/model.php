<?php
class Cms_CategoryLanguages extends Doctrine_Record
{
	public function setUp() 
    {
		$this->setAttribute(Doctrine::ATTR_COLL_KEY, 'language_id');
		$this->hasOne('Cms_Category as category', array('local' => 'category_id', 'foreign' => 'id', 'onDelete' => 'CASCADE'));
	}
 
	public function setTableDefinition() 
	{
		$this->hasColumn('name', 'string',256);
		$this->hasColumn('category_id', 'integer',11);
		$this->hasColumn('language_id', 'integer',11);
		$this->option('collate', 'utf8_unicode_ci');
		$this->option('charset', 'utf8');
		$this->option('type', 'INNODB');
		$this->index('index_category', array('fields' => 'category_id'));
		$this->index('index_language', array('fields' => 'language_id'));
	}
}
class Cms_Category extends Doctrine_Record 
{
 
	public function setUp() 
    {
		$this->ownsMany('Cms_CategoryLanguages as langs', array('local' => 'id', 'foreign' => 'category_id'));
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
		$this->index('index_parent', array('fields' => 'parent'));
	}
}
