<?php
class Cms_Category extends Doctrine_Record 
{
 
	public static function initMetadata($class) 
    {
        $class->setColumn('created', 'timestamp');
		$class->setColumn('parent', 'integer', 11);
		$class->setColumn('position', 'integer', 3);
		$class->setColumn('active', 'integer', 11);
		$class->setTableOption('collate', 'utf8_unicode_ci');
		$class->setTableOption('charset', 'utf8');
		$class->setTableOption('type', 'INNODB');
		$class->addIndex('index_parent', array('fields' => array('parent')));
        
		$class->hasMany('Cms_CategoryLanguages as langs', array('local' => 'id', 'foreign' => 'category_id'));
	}
}
