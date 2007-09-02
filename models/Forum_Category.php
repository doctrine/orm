<?php
class Forum_Category extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('root_category_id', 'integer', 10);
        $this->hasColumn('parent_category_id', 'integer', 10);
        $this->hasColumn('name', 'string', 50);
        $this->hasColumn('description', 'string', 99999);
    }
    public function setUp() {
        $this->hasMany('Forum_Category as Subcategory', 'Subcategory.parent_category_id');
        $this->hasOne('Forum_Category as Parent', 'Forum_Category.parent_category_id');
        $this->hasOne('Forum_Category as Rootcategory', 'Forum_Category.root_category_id');
    }
}
