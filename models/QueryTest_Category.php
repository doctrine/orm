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
    public function setTableDefinition()
    {        
        $this->hasColumn('rootCategoryId as rootCategoryId', 'integer', 4,
                array('default' => 0));
        $this->hasColumn('parentCategoryId as parentCategoryId', 'integer', 4,
                array('notnull', 'default' => 0));
        $this->hasColumn('name as name', 'string', 50,
                array('notnull', 'unique'));
        $this->hasColumn('position as position', 'integer', 4,
                array('default' => 0, 'notnull'));
    }

    /**
     * Relations definition.
     */
    public function setUp()
    {
        $this->hasMany('QueryTest_Category as subCategories', 'subCategories.parentCategoryId');
        $this->hasOne('QueryTest_Category as rootCategory', 'QueryTest_Category.rootCategoryId');
        $this->hasMany('QueryTest_Board as boards', 'QueryTest_Board.categoryId');
    }
}
