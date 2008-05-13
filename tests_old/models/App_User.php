<?php
class App_User extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('first_name', 'string', 32);
        $class->setColumn('last_name', 'string', 32);
        $class->setColumn('email', 'string', 128, 'email');
        $class->setColumn('username', 'string', 16, 'unique, nospace');
        $class->setColumn('password', 'string', 128, 'notblank');
        $class->setColumn('country', 'string', 2, 'country');
        $class->setColumn('zipcode', 'string', 9, 'nospace');
        $class->hasMany('App', array('local' => 'id', 'foreign' => 'user_id'));
    }
}
