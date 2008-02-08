<?php
class App extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 32);
        $class->setColumn('user_id', 'integer', 11);
        $class->setColumn('app_category_id', 'integer', 11);
        $class->hasOne('User', array('local' => 'user_id', 'foreign' => 'id'));
        $class->hasOne('App_Category as Category', array('local' => 'app_category_id', 'foreign' => 'id'));
    }     
}

