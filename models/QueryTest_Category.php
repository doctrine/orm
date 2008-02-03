<?php
class QueryTest_Category extends Doctrine_Record
{    
    /**
     * The depth of the category inside the tree.
     * Non-persistent field. 
     * 
     * @var integer
     */
    public $depth;

    /**
     * Table definition.
     */
    public static function initMetadata($class)
    {        
        $class->setColumn('rootCategoryId as rootCategoryId', 'integer', 4,
                array('default' => 0));
        $class->setColumn('parentCategoryId as parentCategoryId', 'integer', 4,
                array('notnull', 'default' => 0));
        $class->setColumn('name as name', 'string', 50,
                array('notnull', 'unique'));
        $class->setColumn('position as position', 'integer', 4,
                array('default' => 0, 'notnull'));
                
        $class->hasMany('QueryTest_Category as subCategories', array('local' => 'id', 'foreign' => 'parentCategoryId'));
        $class->hasOne('QueryTest_Category as rootCategory', array('local' => 'rootCategoryId', 'foreign' => 'id'));
        $class->hasMany('QueryTest_Board as boards', array('local' => 'id', 'foreign' => 'categoryId'));
    }
}
