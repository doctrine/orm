<?php
class App_Category extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 32);
        $this->hasColumn('parent_id', 'integer');
    }
    public function setUp() {
        $this->hasMany('App', 'App.app_category_id');
        $this->hasMany('App_Category as Parent', 'App_Category.parent_id');
    }
}
