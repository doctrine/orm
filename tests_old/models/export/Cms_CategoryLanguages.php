<?php
class Cms_CategoryLanguages extends Doctrine_Entity
{
	public static function initMetadata($class) 
    {
        $class->setColumn('name', 'string',256);
		$class->setColumn('category_id', 'integer',11);
		$class->setColumn('language_id', 'integer',11);
		$class->setTableOption('collate', 'utf8_unicode_ci');
		$class->setTableOption('charset', 'utf8');
		$class->setTableOption('type', 'INNODB');
		$class->addIndex('index_category', array('fields' => array('category_id')));
		$class->addIndex('index_language', array('fields' => array('language_id')));
        
		$class->setAttribute(Doctrine::ATTR_COLL_KEY, 'language_id');
		$class->hasOne('Cms_Category as category', array('local' => 'category_id', 'foreign' => 'id', 'onDelete' => 'CASCADE'));
	}
}
